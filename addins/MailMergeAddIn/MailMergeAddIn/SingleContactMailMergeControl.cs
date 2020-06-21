using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Drawing;
using System.Data;
using System.Linq;
using System.Text;
using System.Windows.Forms;
using System.IO;
using com.rightnow.MailMerge.WebService;
using System.Threading;
using Microsoft.Office.Interop.Word;
using System.Text.RegularExpressions;
using RightNow.AddIns.AddInViews;

namespace MailMergeAddIn
{
    public partial class SingleContactMailMergeControl : UserControl
    {
        private string tmplDirectory;
        private List<string> mergeFields;
        private com.rightnow.MailMerge.WebService.Column[] reportColumns;
        private SynchronizationContext syncContext;
        private int cIdColIdx = 0; // when we have a contacts.c_id column, this is that columns index
        private bool isLoading = false; // set when we are loaing from a file, so we don't ove rwrite the data map after the forked process gets the columns off the report

        private List<String> contactProperties;
        private List<String> contactValues;

        private SortedDictionary<string, string> recordData;

        private IGlobalContext _context;

        private string _defaultTemplDir = "J:\\RightNow";
        // we set this so we can test to see if it's true when the content tab add-in
        // is being closed... if this is true, we will prevent it from being closed.
        public bool MergeInProgress { get; private set; }
        private string _mergeInProgressErrorStr = "A merge is currently in progress. Please wait until it is complete before continuing.";
        public string MergeInProgressErrorStr
        {
            get { return this._mergeInProgressErrorStr; }
            set { this._mergeInProgressErrorStr = value; }
        }

        public string selectedTemplate;

        
        public SingleContactMailMergeControl()
        {
        }

        /**
         * remove
         */
        public SingleContactMailMergeControl(List<System.String> contactProperties, List<System.String> contactValues)
        {
            if (System.Diagnostics.Process.GetCurrentProcess().ProcessName != "devenv")
            {
              
                // all UI elements are disabled except both group boxes and the Template label, textbox and browse button
                // the tool strip is also enabled, as settings can be loaded
                InitializeComponent();

                this.contactProperties = contactProperties;
                this.contactValues = contactValues;

                
                // TODO: fork a process here to call web service and get any saved settings for this user
                // it might be better to do this in the ribbon actually and store the information in a singleton
                // so it's there when the content pane is opened (where this control resides)

                mergeFields = new List<string>();

                // setup synchronization context
                syncContext = SynchronizationContext.Current;
                if (syncContext == null)
                    syncContext = new SynchronizationContext();

                // set this controls dock to fill
                this.Dock = DockStyle.Fill;

                // set initial working directory for templates (supports XP - W7) and load tmpls from that dir
                getTemplateFileNames(this._defaultTemplDir);

                // Get first name
                int fni = 0;
                String firstName = "";
                foreach (String contactProperty in contactProperties)
                {
                    if (contactProperty == "NameFirst") firstName = contactValues[fni];
                    ++fni;
                }

                // Get last name
                int lni = 0;
                String lastName = "";
                foreach (String contactProperty in contactProperties)
                {
                    if (contactProperty == "NameLast") lastName = contactValues[lni];
                    ++lni;
                }

                lblContactFullName.Text = firstName + " " + lastName;
                lblReportResultCount.Text = "1 records will be merged.";
                
                // Whenever it sets the data source, the data source property literally inserts a blank element to the start of this.contactProperties.
                // Add a blank element to the start of the contact values to fix it.
                singleContactDataMapListView.ContactFieldDataSource = this.contactProperties;
                this.contactValues.Insert(0, "");



                
            }
        }

        /**
         * testing
         */
        public SingleContactMailMergeControl(SortedDictionary<string, string> recordData, IGlobalContext context, string defaultTemplDir, string templateCustomField)
        {
            if (System.Diagnostics.Process.GetCurrentProcess().ProcessName != "devenv")
            {
                this._defaultTemplDir = defaultTemplDir;
                this._context = context;
                
                // all UI elements are disabled except both group boxes and the Template label, textbox and browse button
                // the tool strip is also enabled, as settings can be loaded
                InitializeComponent();
                this.recordData = recordData;
                mergeFields = new List<string>();

                // setup synchronization context
                syncContext = SynchronizationContext.Current;
                if (syncContext == null)
                {
                    syncContext = new SynchronizationContext();
                }

                this.Dock = DockStyle.Fill;
                getTemplateFileNames(this._defaultTemplDir);

                // Get first name
                int fni = 0;

                String firstName = "";
                if (recordData.ContainsKey("Contact: Name: First"))
                {
                    recordData.TryGetValue("Contact: Name: First", out firstName);
                }
                //foreach (String contactProperty in contactProperties)
                //{
                //    if (contactProperty == "NameFirst") firstName = contactValues[fni];
                //    ++fni;
                //}

                // Get last name
                int lni = 0;
                String lastName = "";
                if (recordData.ContainsKey("Contact: Name: Last"))
                {
                    recordData.TryGetValue("Contact: Name: Last", out lastName);
                }
                //foreach (String contactProperty in contactProperties)
                //{
                //    if (contactProperty == "NameLast") lastName = contactValues[lni];
                //    ++lni;
                //}

                lblContactFullName.Text = firstName + " " + lastName;
                lblReportResultCount.Text = "1 records will be merged.";

                // Whenever it sets the data source, the data source property literally inserts a blank element to the start of this.contactProperties.
                // Add a blank element to the start of the contact values to fix it.
             //   singleContactDataMapListView.ContactFieldDataSource = this.contactProperties;
              //  this.contactValues.Insert(0, "");
                singleContactDataMapListView.ContactFieldDataSourceDict = this.recordData;
                
                if (this._context.CanEditAdminSettings)
                {
                    this.btnSave.Visible = true;
                    this.lblSave.Visible = true;
                }

                //see if we can load the default template
                if( recordData.ContainsKey(templateCustomField)){
                    string templateName;
                    recordData.TryGetValue(templateCustomField, out templateName);
                    if (templateName.Length > 0)
                    {
                        int cmboIdx = cmboBoxTmpl.FindStringExact(templateName);
                        if (cmboIdx > -1)
                        {
                            cmboBoxTmpl.SelectedIndex = cmboIdx;
                        }
                    }

                }

            }
        }
        private void cmboBoxTmpl_SelectedIndexChanged(object sender, EventArgs e)
        {
            FileInfo selectedTemplate = (FileInfo)((ComboBox)sender).SelectedItem;

            if (selectedTemplate.Name == this.selectedTemplate)
            {
                return;
            }
            else
            {
                this.selectedTemplate =selectedTemplate.Name;
            }

            // enable the next three rows in the UI (output directory - analytics report)
            lblOutputDir.Enabled = true;
            txtOutputDir.Enabled = true;
            btnBrowseOutputDir.Enabled = true;

            lblFileFormat.Enabled = true;
            txtFileFormat.Enabled = true;

            // If the contact exists, then merge can be attached.  If not, then the merge cannot be attached.
            int c_id = 0;
            String contactValue = "";
            /**
             * testing
             */
            contactValue = "123";
            c_id = Convert.ToInt32(contactValue);

            if (c_id > 0)
                cboxAttachToContact.Enabled = true;
            else
                cboxAttachToContact.Enabled = false;
            cBoxPdf.Enabled = true;
            cboxSendToPrinter.Enabled = true;

            btnMerge.Enabled = true;
            btnPreview.Enabled = true;

            // TODO: profile with tmpl parsing here vs doing it when the report is selected; keeping in mind that
            // we have to make a web service call when the report is selected too.
            mergeFields = MailMergeDocument.getMergeFields(Path.Combine(tmplDirectory,  cmboBoxTmpl.SelectedItem.ToString()));

            // load them into the context menu for the file format
            contextMenuStrip.Items.Clear();
            foreach (string fld in mergeFields)
            {
                contextMenuStrip.Items.Add(fld, null, new EventHandler(onFileFormatContextMenuClick));
            }

            // clear the file format too, in case there are variables
            // TODO: possibly remove this?
            txtFileFormat.Text = "";

            // spawn this thread to get the merge fields
            ThreadPool.QueueUserWorkItem(
                delegate(object state)
                {
                    syncContext.Send(new SendOrPostCallback(delegate(object someState)
                    {
                        if (isLoading == false)
                        {
                            singleContactDataMapListView.Items.Clear();
                            foreach (string val in mergeFields)
                            {
                                singleContactDataMapListView.Items.Add(new ListViewItem(new string[] { val, "" }));
                            }
                        }
                        else
                        {
                            singleContactDataMapListView.Enabled = true;
                            isLoading = false; // set it
                        }
                        this.loadSettings();
                    }), null);
                }
            );

        }

        private void getTemplateFileNames(string path)
        {
            if (path == null || path.Length < 1)
            {
                return;
            }
            DirectoryInfo dInfo = new DirectoryInfo(path);
            FileInfo[] tmplFiles;
            try
            {
                tmplFiles = dInfo.GetFiles("*.dotx");
            }
            catch (Exception e)
            {
                // tmplFiles will be set = empty.
                tmplFiles = new FileInfo[0];
            }

            cmboBoxTmpl.Items.Clear();
            cmboBoxTmpl.Items.AddRange(tmplFiles);

            tmplDirectory = path;

            if (cmboBoxTmpl.Items.Count > 0)
                cmboBoxTmpl.SelectedIndex = 0;
            else
            {
                // disable the next three rows in the UI (output directory - analytics report)
                lblOutputDir.Enabled = false;
                txtOutputDir.Enabled = false;
                btnBrowseOutputDir.Enabled = false;

                lblFileFormat.Enabled = false;
                txtFileFormat.Enabled = false;
                
                cboxAttachToContact.Enabled = true;//TODO:I need this to be enabled on load and the three below them
                cBoxPdf.Enabled = true;
                cboxSendToPrinter.Enabled = true;

                btnMerge.Enabled = false;
                btnPreview.Enabled = false;
            }
        }

        private void btnBrowseTmpl_Click(object sender, EventArgs e)
        {
            DialogResult res = folderBrowserTmplDialog.ShowDialog();
            if (res == DialogResult.OK)
            {
                getTemplateFileNames(folderBrowserTmplDialog.SelectedPath.ToString());
            }
        }

        private void backgroundWorker_DoWork(object sender, DoWorkEventArgs e)
        {
            BackgroundWorker worker = (BackgroundWorker)sender;
            Setting arg = (Setting)e.Argument;

           // RnConnect connect = RnConnect.getInstance();

            string newFileName = "";
            string outFile;
            List<String> finalOutputFiles = new List<String>();

            // is this a preview? if so, grab a random row from the result set
            // and call the preview function
            if (arg.IsPreview == true)
            {
                
                outFile = Path.GetTempFileName();
                MailMergeDocument.mergeDocument(Path.Combine(arg.TmplDir, arg.TmplFile), outFile, arg.DataMap);
                WordInterop.openDocument(outFile);

                // can't clean up the temporary file... it is locked by word

                return;
            }

            // setup the progress bar... needs to be here since the maximum is the number 
            // returned from the report            
            syncContext.Send(new SendOrPostCallback(delegate(object state)
            {
                progressBar.Value = 0;
                progressBar.Maximum = 1;
                progressBar.Visible = true;
                btnStopMerge.Visible = true;
                btnStopMerge.Enabled = true; // in case we stopped a previous merge
                MergeInProgress = true;

                gBoxSettings.Enabled = false;
                gBoxDataMap.Enabled = false;
                btnPreview.Enabled = false;
                btnMerge.Enabled = false;
            }), null);

            // iterate over each result from the report
            // I left this in because there's a progress bar (even though it's one record)
            for (int i = 0; i < 1; ++i)
            {
                // has the process been stopped?
                if (worker.CancellationPending)
                {
                    e.Cancel = true;
                    return;
                }

                // are we creating multiple documents or just one?
                newFileName = arg.FileFormat; // reset this for each row
                
                // we are creating a single document here...

                foreach (DataMapItem dmItem in arg.DataMap)
                {
            
                    // get the file format variable
                    string fVar = getFileFormatVarFromMergeField(dmItem.TmplFld);
                    if (newFileName.IndexOf(fVar) >= 0)
                        newFileName = newFileName.Replace(fVar, dmItem.Value);
                }

                // if it's the first merge file being created, create it with the real filename
                outFile = string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), newFileName));

                // call the merge function with the data map (contains the merge fields and the replacement value)
                MailMergeDocument.mergeDocument(Path.Combine(arg.TmplDir, arg.TmplFile), outFile, arg.DataMap);
                // Convert to pdf.
                // Final output is complete, add it to the list of final output files.
                if (arg.Pdf)
                {
                    MailMergeDocument.convertToPdf(outFile);
                    finalOutputFiles.Add(Regex.Replace(outFile, "\\.docx$", ".pdf"));
                }
                else
                {
                    finalOutputFiles.Add(outFile);
                }
                

                if (arg.AttachToContact)
                {
                    cwsModel dataModel = cwsModel.getInstance();
                    string contentType = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
                    string userFName = string.Format("{0}.docx", newFileName);
                    string fileLocation = outFile;
                    if (arg.Pdf)
                    {
                        contentType = "application/pdf";
                        userFName = string.Format("{0}.pdf", newFileName);
                        fileLocation = Regex.Replace(outFile, "\\.docx$", ".pdf");
                    }
                   
                    if( ! dataModel.attachFileToRecordCurrentWorkspace(fileLocation, userFName, contentType))
                    {
                        MessageBox.Show("There was an error attaching the file to the record");
                    }
                   

                    // attach to the contact
                    //int c_id = 0;
                    //int cidLookupIndex = 0;
                    //String contactIDValue = "";
                    //foreach (String contactProperty in contactProperties)
                    //{
                    //    if (contactProperty == "ID")
                    //        contactIDValue = contactValues[cidLookupIndex];
                    //    ++cidLookupIndex;
                    //}
                    //c_id = Convert.ToInt32(contactIDValue);
                    //if (arg.Pdf)
                    //{
                    //    connect.attachFileToContact(c_id, Regex.Replace(outFile, "\\.docx$", ".pdf"), string.Format("{0}.pdf", newFileName));
                    //}
                    //else
                    //{
                    //    connect.attachFileToContact(c_id, outFile, string.Format("{0}.docx", newFileName));
                    //}
                }

                // update the progress bar
                syncContext.Send(new SendOrPostCallback(delegate(object someState)
                {
                    progressBar.Value = i + 1;
                }), null);
            }

            // now that all of the merge results have been completed, check to see if we are printing
            // the single final result document; if so, print it
            if (arg.SingleDoc && arg.AutoPrint)
            {
                if (newFileName == "")
                { // Word can't open ".docx".
                    File.Copy(string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), newFileName)), string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), "tmpprint")));
                    WordInterop.sendToPrinter(string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), "tmpprint")));
                    File.Delete(string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), "tmpprint")));
                }
                else
                {
                    WordInterop.sendToPrinter(string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), newFileName)));
                }
                
            }

            if (arg.Pdf)
            {
                File.Delete(string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), newFileName)));
            }

            // At the very end, move all the output files to the output dir.
            foreach (String finalOutputFile in finalOutputFiles)
            {
                //if (File.Exists(Path.Combine(arg.OutputDir, finalOutputFile.Split('\\').Last())))
                //{
                //    File.Delete(Path.Combine(arg.OutputDir, finalOutputFile.Split('\\').Last()));
                //}
                File.Copy(finalOutputFile, Path.Combine(arg.OutputDir, finalOutputFile.Split('\\').Last()), true);
                File.Delete(finalOutputFile);
            }

            MessageBox.Show("The merge was successful!", "Merge Success");
        }

        public bool doValidation()
        {
            // check to see if the output directory exists and is writable
            if (!validateOutputDirectory())
            {
                txtOutputDir.Focus();
                return false;
            }

            // check to make sure that there is a value in the filename format
            if (txtFileFormat.Text.Length == 0)
            {
                MessageBox.Show("Please enter a filename format.", "Filename format");
                txtFileFormat.Focus();
                return false;
            }

            // check to see if there are any rows in the data map that aren't mapped
            if (!singleContactDataMapListView.isDataMapValid())
            {
                if (MessageBox.Show("There are items in the data map that have not been resolved. Do you wish to continue?", "Confirm continue", MessageBoxButtons.YesNo) == DialogResult.No)
                    return false;
            }

            return true;
        }

        private void btnMerge_Click(object sender, EventArgs e)
        {
            if (!doValidation())
                return;

            // TODO: are we performing a merge now? or in the future?

            // TODO: if now, start the merge, otherwise write to the web service

            if (MessageBox.Show("Are you sure you want to perform the mail merge?", "Confirm continue", MessageBoxButtons.YesNo) == DialogResult.No)
                return;

            backgroundWorker.RunWorkerAsync(getCurrentSettings());
        }


        private void btnPreview_Click(object sender, EventArgs e)
        {
            if (!doValidation())
                return;

            Setting setting = getCurrentSettings();
            setting.IsPreview = true;

            backgroundWorker.RunWorkerAsync(setting);
        }

        private void dtpSchedule_ValueChanged(object sender, EventArgs e)
        {
            DateTimePicker obj = (DateTimePicker)sender;
            if (obj.Value < DateTime.Now)
            {
                // set the default schedule to be two hours in the future
                obj.Value = DateTime.Now.AddHours(1);
                MessageBox.Show("You have selected a date that occurs in the past. Please select a new date.", "Schedule Error");
            }
        }

        private bool validateOutputDirectory()
        {
            try
            {
                if (txtOutputDir.Text.ToString() == "")
                {
                    MessageBox.Show("No output directory specified.", "Output directory");
                    return false;
                }

                // does the directory exist?
                if (!Directory.Exists(txtOutputDir.Text.ToString()))
                {
                    // step 1a: would you like to create it?
                    if (MessageBox.Show(string.Format("Directory '{0}' does not exist. Would you like to create it?", txtOutputDir.Text), "Directory create", MessageBoxButtons.YesNo) == DialogResult.Yes)
                    {
                        Directory.CreateDirectory(txtOutputDir.Text.ToString());
                        if (!Directory.Exists(txtOutputDir.Text.ToString()))
                        {
                            MessageBox.Show(string.Format("There was an error creating '{0}'.", txtOutputDir.Text), "Directory create error");
                            return false;
                        }
                    }
                    else
                    {
                        return false;
                    }
                }
                // NOTE: originally i was planning on checking to see if the directory was writable too... that's a lot of work
                // in Windows and I think it's better to just try writing to it and catching the IO exception if there is one
            }
            catch { return false; }

            return true;
        }

        private void btnBrowseOutputDir_Click(object sender, EventArgs e)
        {

            DialogResult res = folderBrowserOutputDirDialog.ShowDialog();
            if (res == DialogResult.OK)
            {
                txtOutputDir.Text = folderBrowserOutputDirDialog.SelectedPath;
            }
        }

        private string getFileFormatVarFromMergeField(string fld)
        {
            return string.Format("${0}", fld.Replace('«', ' ').Replace('»', ' ').Trim());
        }

        private Setting getCurrentSettings()
        {
            Setting s = new Setting();
            s.AcctId = AutoClient.globalContext.AccountId;
            //s.AcctId = this._context.AccountId;
            s.TmplFile = cmboBoxTmpl.SelectedItem.ToString();
            s.TmplDir = tmplDirectory;
            s.OutputDir = txtOutputDir.Text;
            s.FileFormat = txtFileFormat.Text;
            s.SingleDoc = true; // Always true for single contact merge.
            s.AutoPrint = cboxSendToPrinter.Checked;
            s.Pdf = cBoxPdf.Checked;
            // since attaching to contact is dependent on the report having contacts.c_id
            // make sure that the cbox is also enabled... don't just take the checked value
            s.AttachToContact = (cboxAttachToContact.Enabled && cboxAttachToContact.Checked) ? true : false;
            s.DataMap = getDataMap();
            s.MergeType = "SingleContactMailMerge";
            
            return s;
        }

        private void setCurrentSettings(Setting s)
        {
           // getTemplateFileNames(s.TmplDir);
            // select the combo box, hopefully this will actually setup the data map, then we
            // re-add it below
            //cmboBoxTmpl.SelectedIndex = 0;
            //for (int i = 0; i < cmboBoxTmpl.Items.Count; ++i)
            //{
            //    if (cmboBoxTmpl.Items[i].ToString() == s.TmplFile)
            //    {
            //        cmboBoxTmpl.SelectedIndex = i;
            //        break;
            //    }
            //}
            //tmplDirectory = s.TmplDir;

            txtOutputDir.Text = s.OutputDir;
            txtFileFormat.Text = s.FileFormat;

            // so the datamap won't be overwritten when
            // the report index is changed
            isLoading = true;
            singleContactDataMapListView.Enabled = false; // so the data map can't be changed until the columns are populated in the data source

            cboxSendToPrinter.Checked = s.AutoPrint;
            cboxAttachToContact.Checked = s.AttachToContact;
            cBoxPdf.Checked = s.Pdf;

            // clear the data map 
            foreach (ListViewItem item in singleContactDataMapListView.Items)
            {
                singleContactDataMapListView.changeRowRecordValue(item.SubItems[0].Text, "");
            }
            // data source, it should still be valid for the selected report we added above
            syncContext.Send(new SendOrPostCallback(delegate(object someState)
            {

                foreach (DataMapItem dmItem in s.DataMap)
                {
                    if (s.MergeType == "SingleContactMailMerge")
                    {
                        singleContactDataMapListView.changeRowRecordValue(dmItem.TmplFld, dmItem.RntFld);
                    }
                    else
                    {
                        // singleContactDataMapListView.Items.Add(new ListViewItem(new string[] { dmItem.TmplFld, "" }));
                    }
                }
            }), null);
            singleContactDataMapListView.Enabled = true;
            isLoading = false;
        }

        private DataMapItem[] getDataMap()
        {
            DataMapItem[] dataMap = new DataMapItem[singleContactDataMapListView.Items.Count];
            for (int i = 0; i < dataMap.Length; ++i)
            {
                dataMap[i] = new DataMapItem();
                dataMap[i].TmplFld = singleContactDataMapListView.Items[i].SubItems[0].Text;
                Object tag = singleContactDataMapListView.Items[i].SubItems[1].Tag;
                if (singleContactDataMapListView.Items[i].SubItems[1].Tag is KeyValuePair<string, string>)
                {
                    tag = (KeyValuePair<string, string>)tag;
                    dataMap[i].RntFld = ((KeyValuePair<string, string>)singleContactDataMapListView.Items[i].SubItems[1].Tag).Key;
                }
                else
                {
                    dataMap[i].RntFld = dataMap[i].RntFld = singleContactDataMapListView.Items[i].SubItems[1].Text;
                }
                dataMap[i].Value = singleContactDataMapListView.Items[i].SubItems[1].Text == null ? "" : singleContactDataMapListView.Items[i].SubItems[1].Text;
            }

            return dataMap;
        }

        private void onFileFormatContextMenuClick(object sender, EventArgs e)
        {
            ToolStripItem obj = (ToolStripItem)sender;

            int pos = txtFileFormat.SelectionStart;
            string var = getFileFormatVarFromMergeField(obj.Text);
            txtFileFormat.Text = txtFileFormat.Text.Insert(pos, var);
            txtFileFormat.SelectionStart = pos + var.Length; // position cursor after variable that was inserted
        }

        private void btnStopMerge_Click(object sender, EventArgs e)
        {
            backgroundWorker.CancelAsync();
            btnStopMerge.Enabled = false;
        }

        private void backgroundWorker_RunWorkerCompleted(object sender, RunWorkerCompletedEventArgs e)
        {
            progressBar.Visible = false;
            btnStopMerge.Visible = false;
            MergeInProgress = false;

            gBoxSettings.Enabled = true;
            gBoxDataMap.Enabled = true;
            btnPreview.Enabled = true;
            btnMerge.Enabled = true;
        }

        private void tsBtnLoad_Click(object sender, EventArgs e)
        {
            loadSettings();
        }


        public void loadSettings()
        {
            if (MergeInProgress == false)
            {
                if (cmboBoxTmpl.Text.Length > 1)
                {
                    Setting config;
                    try
                    {
                        cwsModel dataModel = cwsModel.getInstance();
                        config = dataModel.getAppSettings(this.cmboBoxTmpl.Text);
                    }
                    catch (Exception e)
                    {
                        MessageBox.Show("There was an error loading settings.  Defaults will be used. The server said: " + e.Message, "Invalid Settings");
                        config = new Setting();
                    }
                    config.MergeType = "SingleContactMailMerge";
                    setCurrentSettings(config);
                }
                else
                {
                    MessageBox.Show("Please specify a template to load.","No Template Selected");
                }
                //DialogResult dr = openFileDialog.ShowDialog();

                //if (dr == DialogResult.OK)
                //{
                //    // open the file and deserialize the JSON
                //    // then load the settings...
                //    TextReader tr = new StreamReader(openFileDialog.FileName);
                //    Setting config = Request.fromJsonString<Setting>(tr.ReadToEnd());
                //    setCurrentSettings(config);
                //    tr.Close();
                //}
            }
            else
            {
                throw new Exception(MergeInProgressErrorStr);
            }
        }

        private void btnOpen_Click(object sender, EventArgs e)
        {
            try
            {
                loadSettings(); 
            }
            catch (Exception ex)
            {
                MessageBox.Show(ex.Message.ToString(), "Merge in progress");
            }
        }


        //not deleted to be used as reference for multi record merge
        //public void OpenSettings()
        //{
        //    if (MergeInProgress == false)
        //    {
        //        DialogResult dr = openFileDialog.ShowDialog();

        //        if (dr == DialogResult.OK)
        //        {
        //            // open the file and deserialize the JSON
        //            // then load the settings...
        //            TextReader tr = new StreamReader(openFileDialog.FileName);
        //            Setting config = Request.fromJsonString<Setting>(tr.ReadToEnd());
        //            setCurrentSettings(config);
        //            tr.Close();
        //        }
        //    }
        //    else
        //    {
        //        throw new Exception(MergeInProgressErrorStr);
        //    }
        //}

        //private void tsBtnSave_Click(object sender, EventArgs e)
        //{
        //    SaveSettings();
        //}

        //public void SaveSettings()
        //{
        //    if (MergeInProgress == false)
        //    {
        //        DialogResult dr = saveFileDialog.ShowDialog();

        //        if (dr == DialogResult.OK)
        //        {
        //            Setting config = getCurrentSettings();

        //            // now write them to a file...
        //            TextWriter tw = new StreamWriter(saveFileDialog.FileName);
        //            tw.WriteLine(Request.toJsonString((object)config));
        //            tw.Close();
        //        }
        //    }
        //    else
        //    {
        //        throw new Exception(MergeInProgressErrorStr);
        //    }
        //}

        public void storeSettings()
        {
            if (MergeInProgress != false)
            {
                throw new Exception(MergeInProgressErrorStr);
                return;
            }
            Setting config = getCurrentSettings();
            cwsModel dataModel = cwsModel.getInstance();
            dataModel.storeAppSettings(config);
        }


        private void cboxMergeSingleDoc_CheckedChanged(object sender, EventArgs e)
        {
            // if this is checked, then enable the send to printer option, otherwise
            // keep it disabled
            System.Windows.Forms.CheckBox cbox = (System.Windows.Forms.CheckBox)sender;
            cboxSendToPrinter.Enabled = cbox.Checked;
        }

        private void folderBrowserDialog_HelpRequest(object sender, EventArgs e)
        {

        }

        private void cmboBoxFileFormat_SelectedIndexChanged(object sender, EventArgs e)
        {

        }

        private void cBoxPdf_CheckedChanged(object sender, EventArgs e)
        {
            if (cBoxPdf.Checked)
            {
                lblFileExt.Text = ".pdf";
            }
            else
            {
                lblFileExt.Text = ".docx";
            }
        }

        private void btnSave_Click(object sender, EventArgs e)
        {
            try
            {
                if (doValidation())
                {
                    storeSettings();
                }
                else
                {
                    MessageBox.Show("Unable to save settings", "Error");
                    return;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show(ex.Message.ToString(), "Unable to save");
                return;
            }
            MessageBox.Show("Settings Saved", "Success");
        }

        
        private void lblContactFullName_Click(object sender, EventArgs e)
        {

        }

        private void singleContactDataMapListView_SelectedIndexChanged(object sender, EventArgs e)
        {

        }

        
    }
}
