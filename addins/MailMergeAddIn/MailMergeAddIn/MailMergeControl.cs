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
using System.Text.RegularExpressions;
using MailMergeAddIn.RightNowService;
using System.Net;
using System.Web.Script.Serialization;
using System.Web;
using SelectPdf;

namespace MailMergeAddIn
{
    public partial class MailMergeControl : UserControl
    {
        private string tmplDirectory;
        private List<string> mergeFields;
        private Column[] reportColumns;
        private string donationDataLabel = "Donation Data";
        private string reportContactIdHeader = "ID";
        private List<ReportColWithRow> reportData {get; set; }
        
        private int ContactListId { get; set; }

        private RnConnSvc.AnalyticsReport reportHeaders
        {
            get; set;
        }

        private int AcId { get; set; }
        private SynchronizationContext syncContext;
        private bool contactIdColFound = false; // set to true when we're able to pull the contact ID from the report
        private bool isLoading = false; // set when we are loaing from a file, so we don't over write the data map after the forked process gets the columns off the report

        // we set this so we can test to see if it's true when the content tab add-in
        // is being closed... if this is true, we will prevent it from being closed.
        public bool MergeInProgress { get; private set; }
        public string MergeInProgressErrorStr { get; set; } // so it can be changed externally

        public MailMergeControl()
        {
            if (System.Diagnostics.Process.GetCurrentProcess().ProcessName != "devenv")
            {
                // all UI elements are disabled except both group boxes and the Template label, textbox and browse button
                // the tool strip is also enabled, as settings can be loaded
                InitializeComponent();

                // TODO: fork a process here to call web service and get any saved settings for this user
                // it might be better to do this in the ribbon actually and store the information in a singleton
                // so it's there when the content pane is opened (where this control resides)

                mergeFields = new List<string>();

                // setup synchronization context
                syncContext = SynchronizationContext.Current ?? new SynchronizationContext();


                // set this controls dock to fill
                this.Dock = DockStyle.Fill;

                // set initial working directory for templates (supports XP - W7) and load tmpls from that dir
                getTemplateFileNames(addinsSettings.Instance.defaultTemplDir   );

                // populate report list
                if (!string.IsNullOrEmpty(this.txtBoxReportId.Text)  && ValidReportNum(this.txtBoxReportId.Text))
                {
                    AcId = Convert.ToInt32(this.txtBoxReportId.Text);
                    //refreshReportList(Convert.ToInt32(this.txtBoxReportId.Text));
                    SetTemplateAndReportValuesForDataList(Convert.ToInt32(this.txtBoxReportId.Text)); //TODO:determine if this method call is required to in the constructor, now that the repore combobox has been removed
                }

                this.MergeInProgressErrorStr = "A merge is currently in progress. Please wait until it is complete before continuing.";
            }
        }

        private void cmboBoxTmpl_SelectedIndexChanged(object sender, EventArgs e)
        {
            // enable the next three rows in the UI (output directory - analytics report)
            lblOutputDir.Enabled = true;
            txtOutputDir.Enabled = true;
            btnBrowseOutputDir.Enabled = true;

            lblFileFormat.Enabled = true;
            lblFileExt.Enabled = true;
            txtFileFormat.Enabled = true;

            lblReport.Enabled = true;
            //cmboBoxReport.Enabled = true;

            cboxMergeSingleDoc.Enabled = true;
            cBoxPdf.Enabled = true;
            // cboxSendToPrinter.Enabled = true; // ldavison - 2009.09.11 - changed to only allow you to print a single document that contains all of the mail merges

            btnMerge.Enabled = true;
            btnPreview.Enabled = true;

            // TODO: profile with tmpl parsing here vs doing it when the report is selected; keeping in mind that
            // we have to make a web service call when the report is selected too.
            mergeFields = MailMergeDocument.getMergeFields(Path.Combine(tmplDirectory, cmboBoxTmpl.SelectedItem.ToString()));

            // load them into the context menu for the file format
            contextMenuStrip.Items.Clear();
            foreach (string fld in mergeFields)
            {
                contextMenuStrip.Items.Add(fld, null, new EventHandler(onFileFormatContextMenuClick));
            }

            // clear the file format too, in case there are variables
            // TODO: possibly remove this?
            txtFileFormat.Text = "";

            Setting setting = getCurrentSettings();
            //only allowed to check send email to customer if we have an html template.
            if (setting.TmplFile.Contains(".dotx"))
            {
                cboxEmail.Enabled = false;
                cboxEmail.Checked = false;
            }
            else
            {
                cboxEmail.Enabled = true;
            }

            // clear the report selection (which will in turn clear the data map)
          //  cmboBoxReport.SelectedIndex = 0;
        }



        private void getTemplateFileNames(string path)
        {
            path = @"C:/templates"; //TODO: remove this hard coded value 
            DirectoryInfo dInfo = new DirectoryInfo(path);
            FileInfo[] tmplFiles;

            try
            {
                //tmplFiles = dInfo.GetFiles("*.dotx; *.html; *.htm");

                string[] extensions = new[] { ".dotx", ".html", ".htm" };
                tmplFiles = dInfo.EnumerateFiles().Where(f => extensions.Contains(f.Extension.ToLower())).ToArray();

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
                lblFileExt.Enabled = false;
                txtFileFormat.Enabled = false;

                lblReport.Enabled = false;
                //cmboBoxReport.Enabled = false;

                cboxMergeSingleDoc.Enabled = true; //TODO: This needs to be enabled now that the combo Box Ctrl is no longer present. 
                cBoxPdf.Enabled = true;
                // cboxSendToPrinter.Enabled = true; // ldavison - 2009.09.11 - changed to only allow you to print a single document that contains all of the mail merges

                btnMerge.Enabled = false;
                btnPreview.Enabled = false ;

                Setting setting = getCurrentSettings();
                //only allowed to check send email to customer if we have an html template.
                if (setting.TmplFile.Contains(".dotx"))
                {
                    cboxEmail.Enabled = false;
                    cboxEmail.Checked = false;
                }
                else
                {
                    cboxEmail.Enabled = true;
                }
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

            //TODO: run data here Get a report preview....limit to like 1 row...
            //RnConnect connect = RnConnect.getInstance();
            //object[][] reportData = connect.getReportData(arg.AcId);
            //AnalyticsReport reportData = connect.getReportData(arg.AcId);


            string newFileName = "";
            string outFile;
            List<String> finalOutputFiles = new List<String>();
            // is this a preview? if so, grab a random row from the result set
            // and call the preview function
            if (arg.IsPreview == true)
            {
                Random rand = new Random();
                ReportColWithRow rowData = reportData[rand.Next(0, reportData.Count)];

                // build up the data map.
                foreach (DataMapItem dmItem in arg.DataMap)
                {
                    if (dmItem == null)
                    {
                        continue;
                    }
                    string colVal;
                    if (dmItem.RntFld == null)
                    {
                        dmItem.Value = "";
                    }
                    else if (rowData.fields.TryGetValue(dmItem.RntFld, out colVal))
                    {
                        dmItem.Value = colVal;
                    }
                    //we examine attachToContact here because it also indicates we have valid contact data
                    else if (dmItem.RntFld == this.donationDataLabel && arg.ContactIdCheckPassed)
                    {
                        string c_id;
                        string replacmentText = "";
                        if (rowData.fields.TryGetValue(this.reportContactIdHeader, out c_id))
                        {
                            if (c_id != null)
                            {
                                if (Convert.ToInt32(c_id) > 0 && arg.DonationDataReportId > 0)
                                {
                                    replacmentText = cwsModel.getInstance().GetContactDonationDataForRpt(arg.DonationDataReportId, Convert.ToInt32(c_id));
                                }
                            }
                        }
                        dmItem.Value = replacmentText;
                    }
                    else
                    {
                        dmItem.Value = "";
                    }

                }

                outFile = Path.GetTempFileName();

                if (arg.TmplFile.Contains(".dotx"))
                {
                    MailMergeDocument.mergeDocument(Path.Combine(arg.TmplDir, arg.TmplFile), outFile, arg.DataMap);
                    WordInterop.openDocument(outFile);
                }
                else
                {
                    //mergeHtmlDocument handles the open document functionality.
                    MailMergeDocument.mergeHtmlDocument(Path.Combine(arg.TmplDir, arg.TmplFile), outFile, arg.DataMap);
                }
                

                // can't clean up the temporary file... it is locked by word //TODO: use process.Kill to clean up Word 

                return;
            }

            // setup the progress bar... needs to be here since the maximum is the number 
            // returned from the report            
            syncContext.Send(new SendOrPostCallback(delegate(object state)
            {
                progressBar.Value = 0;
                progressBar.Maximum = reportData.Count;
                progressBar.Visible = true;
                btnStopMerge.Visible = true;
                btnStopMerge.Enabled = true; // in case we stopped a previous merge
                MergeInProgress = true;

                gBoxSettings.Enabled = false;
                gBoxDataMap.Enabled = false;
                btnPreview.Enabled = false;
                btnMerge.Enabled = false;
            }), null);

            //we're doing the merge, iterated over the results....
            for (int i = 0; i < reportData.Count; ++i)
            {
                // has the process been stopped?
                if (worker.CancellationPending)
                {
                    e.Cancel = true;
                    return;
                }

                // are we creating multiple documents or just one?
                newFileName = arg.FileFormat; // reset this for each row
                

                if (arg.TmplFile.Contains(".htm") || arg.TmplFile.Contains(".html"))
                {
                    foreach (DataMapItem dmItem in arg.DataMap)
                    {

                        string colVal;
                        if (dmItem.RntFld == null)
                        {
                            dmItem.Value = "";
                        }
                        else if (reportData[i].fields.TryGetValue(dmItem.RntFld, out colVal))
                        {
                            dmItem.Value = colVal;
                        }
                        else if (dmItem.RntFld == this.donationDataLabel)
                        {
                            string c_id;
                            string replacmentText = "";
                            if (reportData[i].fields.TryGetValue(this.reportContactIdHeader, out c_id))
                            {
                                if (c_id != null)
                                {
                                    if (Convert.ToInt32(c_id) > 0 && arg.DonationDataReportId > 0)
                                    {
                                        replacmentText = cwsModel.getInstance().GetContactDonationDataForRpt(arg.DonationDataReportId, Convert.ToInt32(c_id));
                                    }
                                }
                            }
                            dmItem.Value = replacmentText;
                        }
                        else
                        {
                            dmItem.Value = "";
                        }

                        string fVar = getFileFormatVarFromMergeField(dmItem.TmplFld);
                        if (newFileName.IndexOf(fVar) >= 0)
                        {
                            string fileReplaceVal;
                            if (reportData[i].fields.TryGetValue(dmItem.RntFld, out fileReplaceVal))
                            {
                                newFileName = newFileName.Replace(fVar, fileReplaceVal);
                            }
                        }

                    }

                    string contact_id;
                    if (reportData[i].fields.TryGetValue(this.reportContactIdHeader, out contact_id)) {}

                    outFile = string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), newFileName));
                    string emailResult = sendEmailFromScript(arg, contact_id, newFileName, outFile);


                    // update the progress bar
                    syncContext.Send(new SendOrPostCallback(delegate(object someState)
                    {
                        progressBar.Value = i + 1;
                        lblReportResultCount.Text = string.Format("{0} of {1} records merged", i + 1, reportData.Count);
                    }), null);

                }
                else
                {


                    if (arg.SingleDoc == false)
                    {
                        foreach (DataMapItem dmItem in arg.DataMap)
                        {

                            string colVal;
                            if (dmItem.RntFld == null)
                            {
                                dmItem.Value = "";
                            }
                            else if (reportData[i].fields.TryGetValue(dmItem.RntFld, out colVal))
                            {
                                dmItem.Value = colVal;
                            }
                            else if (dmItem.RntFld == this.donationDataLabel)
                            {
                                string c_id;
                                string replacmentText = "";
                                if (reportData[i].fields.TryGetValue(this.reportContactIdHeader, out c_id))
                                {
                                    if (c_id != null)
                                    {
                                        if (Convert.ToInt32(c_id) > 0 && arg.DonationDataReportId > 0)
                                        {
                                            replacmentText = cwsModel.getInstance().GetContactDonationDataForRpt(arg.DonationDataReportId, Convert.ToInt32(c_id));
                                        }
                                    }
                                }
                                dmItem.Value = replacmentText;
                            }
                            else
                            {
                                dmItem.Value = "";
                            }

                            string fVar = getFileFormatVarFromMergeField(dmItem.TmplFld);
                            if (newFileName.IndexOf(fVar) >= 0)
                            {
                                string fileReplaceVal;
                                if (reportData[i].fields.TryGetValue(dmItem.RntFld, out fileReplaceVal))
                                {
                                    newFileName = newFileName.Replace(fVar, fileReplaceVal);
                                }
                            }

                        }

                        // create the output filename
                        outFile = string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), newFileName));
                    }
                    // we are creating a single document here...
                    else
                    {
                        foreach (DataMapItem dmItem in arg.DataMap)
                        {

                            string colVal;
                            if (dmItem.RntFld == null)
                            {
                                dmItem.Value = "";
                            }
                            else if (reportData[i].fields.TryGetValue(dmItem.RntFld, out colVal))
                            {
                                dmItem.Value = colVal;
                            }
                            else if (dmItem.RntFld == this.donationDataLabel)
                            {
                                string c_id;
                                string replacmentText = "";
                                if (reportData[i].fields.TryGetValue(this.reportContactIdHeader, out c_id))
                                {
                                    if (c_id != null)
                                    {
                                        if (Convert.ToInt32(c_id) > 0 && arg.DonationDataReportId > 0)
                                        {
                                            replacmentText = cwsModel.getInstance().GetContactDonationDataForRpt(arg.DonationDataReportId, Convert.ToInt32(c_id));
                                        }
                                    }
                                }
                                dmItem.Value = replacmentText;
                            }
                            else
                            {
                                dmItem.Value = "";
                            }

                            // get the file format variable
                            string fVar = getFileFormatVarFromMergeField(dmItem.TmplFld);
                            //if (newFileName.IndexOf(fVar) >= 0)
                            //    newFileName = newFileName.Replace(fVar, (string)reportData[i][dmItem.ColId].ToString());
                            if (newFileName.IndexOf(fVar) >= 0)
                            {
                                string fileReplaceVal;
                                if (reportData[i].fields.TryGetValue(dmItem.RntFld, out fileReplaceVal))
                                {
                                    newFileName = newFileName.Replace(fVar, fileReplaceVal);
                                }
                            }

                        }//end foreach

                        // if it's the first merge file being created, create it with the real filename
                        if (i == 0)
                        {
                            outFile = string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), arg.FileFormat));
                        }
                        // then each additional file, create in a temporary file and append it to the end of the real file
                        else
                        {
                            outFile = Path.GetTempFileName();
                            // outFile ends with tmp, so make it end with docx instead.
                            File.Copy(outFile, Regex.Replace(outFile, "\\.tmp$", ".docx"));
                            outFile = Regex.Replace(outFile, "\\.tmp$", ".docx");
                        }
                    }
                

                    // So at this point, outFile contains the output path and filename which is either:
                    //     1) The regular file format (single document)
                    //     2) The regular file format with replacements (multiple documents)
                    // And, the newFilename contains the output filename which is always:
                    //     3) The regular file format with replacements.
                    // call the merge function with the data map (contains the merge fields and the replacement value)
                    MailMergeDocument.mergeDocument(Path.Combine(arg.TmplDir, arg.TmplFile), outFile, arg.DataMap);
                    string currentMergedDocLocation = outFile;
                    if (arg.Pdf)
                    {
                        MailMergeDocument.convertToPdf(outFile);
                        currentMergedDocLocation = Regex.Replace(currentMergedDocLocation, "\\.docx$", ".pdf");

                        // If it's a single document merge, then the outfile is named either (1) or (2), so rename it to be (3).
                        if (arg.SingleDoc)
                        {
                            try
                            {
                                if (File.Exists(string.Format("{0}.pdf", Path.Combine(Path.GetTempPath(), newFileName))))
                                {
                                    File.Delete(string.Format("{0}.pdf", Path.Combine(Path.GetTempPath(), newFileName)));
                                }
                                if (i != 0)
                                {
                                    File.Move(Regex.Replace(outFile, "\\.docx$", ".pdf"), string.Format("{0}.pdf", Path.Combine(Path.GetTempPath(), newFileName)));
                                    currentMergedDocLocation = string.Format("{0}.pdf", Path.Combine(Path.GetTempPath(), newFileName));
                                }
                            }
                            catch (Exception ex)
                            {
                                MessageBox.Show("Error in Single Document Mail Merge Attempt " + ex.Message);
                            }
                        }
                    }

                    // Final output is complete, add it to the list of final output files.
                    if (arg.SingleDoc && i == 0)
                    {
                        if (arg.Pdf)
                        {
                            finalOutputFiles.Add(Path.Combine(Path.GetTempPath(), string.Format("{0}.pdf", arg.FileFormat)));
                        }
                        else
                        {
                            finalOutputFiles.Add(Path.Combine(Path.GetTempPath(), string.Format("{0}.docx", arg.FileFormat)));
                        }
                    }
                    else if (!arg.SingleDoc)
                    {
                        if (arg.Pdf)
                        {
                            finalOutputFiles.Add(Path.Combine(Path.GetTempPath(), string.Format("{0}.pdf", newFileName)));
                        }
                        else
                        {
                            finalOutputFiles.Add(Path.Combine(Path.GetTempPath(), string.Format("{0}.docx", newFileName)));
                        }
                    }

                    if (arg.AttachToContact)
                    {
                        // attach to the contact
                        int c_id = 0;
                        try
                        {
                            string c_id_string;
                            if (reportData[i].fields.TryGetValue(this.reportContactIdHeader, out c_id_string))
                            {
                                if (c_id_string != null)
                                {
                                    if (Convert.ToInt32(c_id_string) > 0 )
                                    {
                                        c_id = Convert.ToInt32(c_id_string);
                                    }
                                }
                            }
                        }
                        catch (Exception ex)
                        {
                            MessageBox.Show("Error encontered determining contact Id value " + ex.Message);
                        }

                        if (c_id > 0)
                        {
                            try
                            {
                                if (arg.Pdf)
                                {
                                    cwsModel.getInstance().addFileToContact(currentMergedDocLocation, string.Format("{0}.pdf", newFileName), "application/pdf", c_id);
                                }
                                else
                                {
                                    cwsModel.getInstance().addFileToContact(currentMergedDocLocation, string.Format("{0}.docx", newFileName), "application/vnd.openxmlformats-officedocument.wordprocessingml.document", c_id);
                                }
                            }
                            catch (Exception ex)
                            {

                            }
                        }
                    }

                    // after the document has been merged, are we merging it back into a single document?
                    // NOTE: we do this after we have attached the document to the contact as we will also
                    // unlink the document (as it's a temporary file)
                    if (arg.SingleDoc)
                    {
                        // skip the first, document... it's the one we merge all subsequent documents into
                        if (i > 0)
                        {
                            string mainFile = string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), arg.FileFormat));
                            MailMergeDocument.combineDocuments(mainFile, outFile, "AltChunk" + i);

                            // now unlink it
                            File.Delete(outFile);
                        }
                    }

                    // update the progress bar
                    syncContext.Send(new SendOrPostCallback(delegate(object someState)
                    {
                        progressBar.Value = i + 1;
                        lblReportResultCount.Text = string.Format("{0} of {1} records merged", i+1, reportData.Count);
                    }), null);

                }//end if
            }


            if (arg.TmplFile.Contains(".htm") || arg.TmplFile.Contains(".html"))
            {
                //do not do the following if an html template
            }
            else 
            { 
                // now that all of the merge results have been completed, check to see if we are printing
                // the single final result document; if so, print it
                if (arg.SingleDoc && arg.AutoPrint)
                {
                    WordInterop.sendToPrinter(string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), arg.FileFormat)));
                }

                if (arg.SingleDoc && arg.Pdf)
                {
                    // Convert the single doc to a pdf
                    string singleDoc = string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), arg.FileFormat));
                    MailMergeDocument.convertToPdf(singleDoc);
                    // Delete the docx
                    File.Delete(singleDoc);
                }
                if (!arg.SingleDoc && arg.Pdf)
                {
                    // Delete the docx
                    File.Delete(string.Format("{0}.docx", Path.Combine(Path.GetTempPath(), newFileName)));
                }

                // At the very end, move all the output files to the output dir.
                foreach (String finalOutputFile in finalOutputFiles)
                {
                    File.Copy(finalOutputFile, Path.Combine(arg.OutputDir, finalOutputFile.Split('\\').Last()), true);
                }
                foreach (String finalOutputFile in finalOutputFiles)
                {
                    File.Delete(finalOutputFile);
                }
            }//end if
            MessageBox.Show("The merge was successful!", "Merge Success");
        }

        private void tsBtnRefreshReports_Click(object sender, EventArgs e)
        {
           // refreshReportList();
        }

        public string sendEmailFromScript(Setting arg, string contactID, string newFileName, string outputFile)
        {
            string myResponse = "";
            JavaScriptSerializer serializer = new JavaScriptSerializer();
            var jsonDataMap = serializer.Serialize(arg.DataMap);
            var templateFile = arg.TmplFile;

            //creating hmtl file
            string fileHtml = System.IO.File.ReadAllText(Path.Combine(arg.TmplDir, arg.TmplFile));

            foreach (DataMapItem dmItem in arg.DataMap)
            {
                if (dmItem != null && dmItem.TmplFld != null)
                {
                    fileHtml = fileHtml.Replace(dmItem.TmplFld, dmItem.Value);
                }
            }

           
            // Create a request using a URL that can receive a post. 
            WebRequest request = WebRequest.Create("https://africanewlife--tst.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/MailMerge/sendmail.php");
            // Set the Method property of the request to POST.
            request.Method = "POST";

            
            // Create POST data and convert it to a byte array.
            string postData = "contactId="+contactID+"&emailContact=" + arg.EmailContacts + "&emailBody=" + HttpUtility.UrlEncode(fileHtml);

            byte[] byteArray = Encoding.UTF8.GetBytes(postData);
            // Set the ContentType property of the WebRequest.
            request.ContentType = "application/x-www-form-urlencoded";
            // Set the ContentLength property of the WebRequest.
            request.ContentLength = byteArray.Length;

            // Get the request stream.
            Stream dataStream = request.GetRequestStream();
            // Write the data to the request stream.
            dataStream.Write(byteArray, 0, byteArray.Length);
            // Close the Stream object.
            dataStream.Close();

            // Get the response.
            WebResponse response = request.GetResponse();
            // Display the status.
            Console.WriteLine(((HttpWebResponse)response).StatusDescription);
            // Get the stream containing content returned by the server.
            dataStream = response.GetResponseStream();
            // Open the stream using a StreamReader for easy access.
            StreamReader reader = new StreamReader(dataStream);
            // Read the content.
            string responseFromServer = reader.ReadToEnd();
            // Display the content.
            Console.WriteLine(responseFromServer);
            // Clean up the streams.
            reader.Close();
            dataStream.Close();
            response.Close();

            //bool pdfReturn = saveHtmltoPDF(fileHtml, int.Parse(contactID), outputFile, responseFromServer);

            return myResponse;
        }

        public bool saveHtmltoPDF(string fileHtml, int c_id, string outputFile, string newFileName)
        {

            //TODO - Get this method working to save as pdf and not html files.

            //here we create the pdf and attaach to contact.
            HtmlToPdf converter = new HtmlToPdf();
            PdfDocument doc = converter.ConvertHtmlString(fileHtml);
            doc.Save(outputFile);

            cwsModel.getInstance().addFileToContact(outputFile, string.Format("{0}.pdf", newFileName), "application/pdf", c_id);

            doc.Close();

            File.Delete(outputFile);

            return true;
        }

        public bool doValidation()
        {
            // check to see if the output directory exists and is writable
            if (!validateOutputDirectory())
            {
                txtOutputDir.Focus();
                return false;
            }

            // check to make sure a report is selected
            //if (cmboBoxReport.SelectedIndex <= 0)
            //{
            //    MessageBox.Show("Please select a report.", "Select report");
            //    cmboBoxReport.Focus();
            //    return false;
            //}

            // check to make sure that there is a value in the filename format
            if (txtFileFormat.Text.Length == 0)
            {
                MessageBox.Show("Please enter a filename format.", "Filename format");
                txtFileFormat.Focus();
                return false;
            }

            // check to see if there are any rows in the data map that aren't mapped
            if (!dataMapListView.isDataMapValid())
            {
                if (MessageBox.Show("There are items in the data map that have not been resolved. Do you wish to continue?", "Confirm continue", MessageBoxButtons.YesNo) == DialogResult.No)
                    return false;
            }

            return true;
        }


        private void btnPreview_Click(object sender, EventArgs e)
        {
            if (!doValidation())
                return;

            Setting setting = getCurrentSettings();
            setting.IsPreview = true;
            GetReportRowsForMailMerge(setting);

            backgroundWorker.RunWorkerAsync(setting);
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
            Setting setting = new Setting
            {
                AcctId = AutoClient.globalContext.AccountId,
                TmplFile = cmboBoxTmpl.SelectedItem.ToString(),
                TmplDir = tmplDirectory,
                OutputDir = txtOutputDir.Text,
                FileFormat = txtFileFormat.Text,
                SingleDoc = cboxMergeSingleDoc.Checked,
                AutoPrint = cboxSendToPrinter.Checked,
                Pdf = cBoxPdf.Checked,
                AttachToContact = (cboxAttachToContact.Enabled && cboxAttachToContact.Checked && contactIdColFound) ? true : false,
                DonationDataReportId = (Convert.ToInt32(donationDataReportId.Text) > 0) ? Convert.ToInt32(donationDataReportId.Text) : -1,
                ContactIdCheckPassed = (cboxAttachToContact.Enabled && contactIdColFound) ? true : false,
                EmailContacts = cboxEmail.Checked
            };
       
            if (reportHeaders != null )
            {
                setting.DataMap = getDataMap();
            }
            setting.MergeType = "MailMerge";
         

            return setting;
        }

        private void setCurrentSettings(Setting setting)
        {
            getTemplateFileNames(setting.TmplDir);
            // select the combo box, hopefully this will actually setup the data map, then we
            // re-add it below
            cmboBoxTmpl.SelectedIndex = 0;
            for (int i = 0; i < cmboBoxTmpl.Items.Count; ++i)
            {
                if (cmboBoxTmpl.Items[i].ToString() == setting.TmplFile)
                {
                    cmboBoxTmpl.SelectedIndex = i;
                    break;
                }
            }
            tmplDirectory = setting.TmplDir;

            txtOutputDir.Text = setting.OutputDir;
            txtFileFormat.Text = setting.FileFormat;

            // so the datamap won't be overwritten when
            // the report index is changed
            isLoading = true;
            dataMapListView.Enabled = false; // so the data map can't be changed until the columns are populated in the data source

            // set the index to 0, so it's reset in case you load the same file twice in a row
          //  cmboBoxReport.SelectedIndex = 0;
            //for (int i = 0; i < cmboBoxReport.Items.Count; ++i)
            //{
            //    Report rpt = (Report)cmboBoxReport.Items[i];
            //    if (rpt.AcId == s.AcId)
            //    {
            //        cmboBoxReport.SelectedIndex = i;
            //        break;
            //    }
            //}
            
            // after a report is selected we need to call the "leave" callback so it will get the report
            // data via background process
           // cmboBoxReport_SelectedIndexChanged((object)cmboBoxReport, new EventArgs());

            cboxMergeSingleDoc.Checked = setting.SingleDoc;
            cboxSendToPrinter.Checked = setting.AutoPrint;
            cboxAttachToContact.Checked = setting.AttachToContact;
            cBoxPdf.Checked = setting.Pdf;

            // clear the data map and re-add it, since we aren't changing the combo boxes
            // data source, it should still be valid for the selected report we added above
            syncContext.Send(new SendOrPostCallback(delegate(object someState)
            {
                dataMapListView.Items.Clear();
                foreach (DataMapItem dmItem in setting.DataMap)
                {
                    if (setting.MergeType == "MailMerge")
                    {
                        dataMapListView.Items.Add(new ListViewItem(new string[] {dmItem.TmplFld, dmItem.RntFld}));
                    }
                    else
                    {
                        dataMapListView.Items.Add(new ListViewItem(new string[] {dmItem.TmplFld, ""}));
                    }
                }
            }), null);
        }

        private DataMapItem[] getDataMap()
        {
            DataMapItem[] dataMap = new DataMapItem[dataMapListView.Items.Count];

            for (int dataMapIdx = 0; dataMapIdx < dataMap.Length; ++dataMapIdx)
            {
                dataMap[dataMapIdx] = new DataMapItem();
                dataMap[dataMapIdx].TmplFld = dataMapListView.Items[dataMapIdx].SubItems[0].Text;
                bool foundMatch = false;
                // locate which column number in the report we need for the mapped column name
                for (int reportColIdx = 0; reportColIdx < reportHeaders.Columns.Length; reportColIdx++)
                {
                    RnConnSvc.AnalyticsReportColumn col = reportHeaders.Columns[reportColIdx];
                    if (col.Heading == dataMapListView.Items[dataMapIdx].SubItems[1].Text)
                    {
                        foundMatch = true;
                        dataMap[dataMapIdx].RntFld = dataMapListView.Items[dataMapIdx].SubItems[1].Text;
                        dataMap[dataMapIdx].ColId = reportColIdx;
                        break;
                    }
                }
                if (!foundMatch)
                {
                    if (dataMapListView.Items[dataMapIdx].SubItems[1].Text == this.donationDataLabel)
                    {
                        dataMap[dataMapIdx].RntFld = dataMapListView.Items[dataMapIdx].SubItems[1].Text;
                        dataMap[dataMapIdx].ColId = -1;
                    }
                }
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
            OpenSettings();
        }

        public void OpenSettings()
        {
            if (MergeInProgress == false)
            {
                DialogResult dr = openFileDialog.ShowDialog();

                if (dr == DialogResult.OK)
                {
                    // open the file and deserialize the JSON
                    // then load the settings...
                    TextReader tr = new StreamReader(openFileDialog.FileName);
                    Setting config = Request.fromJsonString<Setting>(tr.ReadToEnd());
                    setCurrentSettings(config);
                    tr.Close();
                }
            }
            else
            {
                throw new Exception(MergeInProgressErrorStr);
            }
        }

        private void tsBtnSave_Click(object sender, EventArgs e)
        {
            SaveSettings();
        }

        public void SaveSettings()
        {
            if (MergeInProgress == false)
            {
                DialogResult dr = saveFileDialog.ShowDialog();

                if (dr == DialogResult.OK)
                {
                    Setting setting = getCurrentSettings();

                    // now write them to a file...
                    TextWriter tw = new StreamWriter(saveFileDialog.FileName);
                    tw.WriteLine(Request.toJsonString((object)setting));
                    tw.Close();
                }
            }
            else
            {
                throw new Exception(MergeInProgressErrorStr);
            }
        }

        private void cboxMergeSingleDoc_CheckedChanged(object sender, EventArgs e)
        {
            // if this is checked, then enable the send to printer option, otherwise
            // keep it disabled
            CheckBox cbox = (CheckBox)sender;
            cboxSendToPrinter.Enabled = cbox.Checked;
        }

        private void folderBrowserDialog_HelpRequest(object sender, EventArgs e)
        {

        }

        private void cBoxPdf_CheckedChanged(object sender, EventArgs e)
        {
            lblFileExt.Text = cBoxPdf.Checked ? ".pdf" : ".docx";
        }

        private void btnMerge_Click(object sender, EventArgs e)
        {
            if (!doValidation())
            {
                return;
            }
            if (MessageBox.Show("Are you sure you want to perform the mail merge?", "Confirm continue", MessageBoxButtons.YesNo) == DialogResult.No)
            {
                return;
            }
            Setting setting = getCurrentSettings();
            GetReportRowsForMailMerge(setting); 
            backgroundWorker.RunWorkerAsync(setting);
           
        }

        private void btnPullReport_Click(object sender, EventArgs e)
        {
            
            if (!string.IsNullOrEmpty(this.txtBoxReportId.Text) && ValidReportNum(this.txtBoxReportId.Text))
            {
                //refreshReportList(Convert.ToInt32(this.txtBoxReportId.Text));
                SetTemplateAndReportValuesForDataList(Convert.ToInt32(this.txtBoxReportId.Text));
            }
            else
            {
                MessageBox.Show("A Positive Numeric value must be entered for the Report Id.");
             }
        }

        public void SetTemplateAndReportValuesForDataList(int acId)
        {
            //RnConnSvc.AnalyticsReport report = new RnConnSvc.AnalyticsReport();
            // svc.getReportData(acId);
            reportHeaders = new RnConnSvc.AnalyticsReport();
            //report = new RnConnSvc.AnalyticsReport();
            

            //int acId = (int)cbox.SelectedValue;
            // string url = string.Format("{0}/custom/mail_merge_api.php?method=getAcColumns&param={1}",
            //AutoClient.globalContext.InterfaceURL, acId);
            var profileId = AutoClient.globalContext.ProfileId; 
                                                                // spawn this thread to get the report columns, is this thread overkill?
            try
            {
                reportHeaders = cwsModel.getInstance().getAnalyticsReportDefinition(acId);
                if (reportHeaders != null && reportHeaders.Columns.Length == 0)
                {
                    MessageBox.Show("No Data located for Report Id " + acId);
                    return;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error connecting to Right Now Database Service attempting report header retrieval " + ex.Message);
            }
            finally
            {
                Cursor.Current = Cursors.WaitCursor;//Hope we never ger here...but just incase. 
            }
            ThreadPool.QueueUserWorkItem(
                delegate (object state)
                {
                    syncContext.Send(new SendOrPostCallback(delegate (object someState)
                    {
                        bool hasCid = false;
                        List<string> reportFields = new List<string>(); //TODO:Replace with report rows ?
                        // foreach (Column column in columns)
                        //foreach (Column column in reportColumns)
                        foreach (var column in reportHeaders.Columns)
                        {
                              if (column.Heading == this.reportContactIdHeader)
                            {
                                hasCid = true; //TODO: Determineif this is needed?
                                contactIdColFound = true;
                            }
                            //reportFields.Add(column.Val.ToString());
                            reportFields.Add(column.Heading);
                        }
                        if (!string.IsNullOrEmpty(this.donationDataReportId.Text) && ValidReportNum(this.donationDataReportId.Text))
                        {
                            reportFields.Add(this.donationDataLabel);
                        }
                      
                        dataMapListView.ReportFieldDataSource = reportFields;

                        // update the list view here from this seperate thread - to prevent someone try trying to select a report field
                        // before the list of report columns is available
                        if (isLoading == false)
                        {
                            dataMapListView.Items.Clear();
                            foreach (string val in mergeFields)
                            {
                                dataMapListView.Items.Add(new ListViewItem(new string[] { val, "" }));
                            }
                        }
                        else
                        {
                            dataMapListView.Enabled = true;
                            isLoading = false; // set it
                        }

                        // should we enable the attach to contact checkbox?
                        //cboxAttachToContact.Enabled = true;  //hasCid; TODO:Determine what field is the new contact ID for the Analyticts report

                        if (reportData == null)
                        {
                            lblReportResultCount.Text = "0 records will be merged.";
                        }
                        else
                        {
                            //lblReportResultCount.Text = string.Format("{0} records will be merged", reportData.Length);
                            lblReportResultCount.Text = string.Format("{0} records will be merged", reportData.Count);
                        }
                        lblReportResultCount.Visible = true;
                        // lblReportResultCount.Visible = false;
                    }), null);
                }
            );

            // spawn another thread to run the report so we can update the UI and inform the agent how many records
            // will get merged...
            //I see no need to call the same method twice, once should be more than enough
            
        }

        public void GetReportRowsForMailMerge(Setting setting)
        {
            
            if (!string.IsNullOrEmpty(this.txtBoxReportId.Text) && ValidReportNum(this.txtBoxReportId.Text))
            {
                AcId = Convert.ToInt32(this.txtBoxReportId.Text);
            }
            
            try
            {
                //SetTemplateAndReportValuesForDataList(AcId);
                reportHeaders = cwsModel.getInstance().getAnalyticsReportDefinition(AcId);
                if (setting.IsPreview)
                {
                    reportData = cwsModel.getInstance().RunAnalyticsReport(AcId, 0, 5);
                }
                else
                {
                    reportData = cwsModel.getInstance().RunAnalyticsReport(AcId, Convert.ToInt32(this.startIdxText.Text), Convert.ToInt32(this.endIdxText.Text));
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error connecting to Right Now Database Service attempting report row retrieval " + ex.Message);
            }
            finally
            {
                Cursor.Current = Cursors.WaitCursor;//Hope we never ger here...but just incase. 
            }

            if (reportData == null || reportData.Count == 0)
            {
                lblReportResultCount.Text = "0 records will be merged.";
            }
            else
            {
                //lblReportResultCount.Text = string.Format("{0} records will be merged", reportData.Length);
                lblReportResultCount.Text = string.Format("{0} records will be merged", reportData.Count);
            }
            lblReportResultCount.Visible = true;    

        }

        public bool ValidReportNum(string reportNumValue)
        {
            var pattren = @"^[0-9]*$";
            var expression = new Regex(pattren);
            var match = expression.Match(reportNumValue);
            return match.Success;
        }

        private void useContactId_CheckedChanged(object sender, EventArgs e)
        {

        }

        private void contactIsID_CheckedChanged(object sender, EventArgs e)
        {
            if(this.contactIsID.Checked){
                this.cboxAttachToContact.Enabled = true;
            }
            else
            {
                this.cboxAttachToContact.Enabled = false;
            }
        }

        private void tableLayoutPanel1_Paint(object sender, PaintEventArgs e)
        {

        }

        private void checkBox1_CheckedChanged(object sender, EventArgs e)
        {

        }
    }
}
