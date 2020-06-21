namespace MailMergeAddIn
{
    partial class SingleContactMailMergeControl
    {
        /// <summary> 
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary> 
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Component Designer generated code

        /// <summary> 
        /// Required method for Designer support - do not modify 
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.components = new System.ComponentModel.Container();
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(SingleContactMailMergeControl));
            this.contextMenuStrip = new System.Windows.Forms.ContextMenuStrip(this.components);
            this.folderBrowserTmplDialog = new System.Windows.Forms.FolderBrowserDialog();
            this.backgroundWorker = new System.ComponentModel.BackgroundWorker();
            this.openFileDialog = new System.Windows.Forms.OpenFileDialog();
            this.saveFileDialog = new System.Windows.Forms.SaveFileDialog();
            this.BottomToolStripPanel = new System.Windows.Forms.ToolStripPanel();
            this.TopToolStripPanel = new System.Windows.Forms.ToolStripPanel();
            this.RightToolStripPanel = new System.Windows.Forms.ToolStripPanel();
            this.LeftToolStripPanel = new System.Windows.Forms.ToolStripPanel();
            this.ContentPanel = new System.Windows.Forms.ToolStripContentPanel();
            this.btnStopMerge = new System.Windows.Forms.Button();
            this.progressBar = new System.Windows.Forms.ProgressBar();
            this.lblReportResultCount = new System.Windows.Forms.Label();
            this.btnPreview = new System.Windows.Forms.Button();
            this.btnMerge = new System.Windows.Forms.Button();
            this.gBoxDataMap = new System.Windows.Forms.GroupBox();
            this.gBoxSettings = new System.Windows.Forms.GroupBox();
            this.lblLoad = new System.Windows.Forms.Label();
            this.lblFileExt = new System.Windows.Forms.Label();
            this.lblSave = new System.Windows.Forms.Label();
            this.lblContactFullName = new System.Windows.Forms.Label();
            this.cBoxPdf = new System.Windows.Forms.CheckBox();
            this.btnOpen = new System.Windows.Forms.Button();
            this.btnSave = new System.Windows.Forms.Button();
            this.btnBrowseOutputDir = new System.Windows.Forms.Button();
            this.btnBrowseTmpl = new System.Windows.Forms.Button();
            this.cboxSendToPrinter = new System.Windows.Forms.CheckBox();
            this.cboxAttachToContact = new System.Windows.Forms.CheckBox();
            this.txtFileFormat = new System.Windows.Forms.TextBox();
            this.txtOutputDir = new System.Windows.Forms.TextBox();
            this.cmboBoxTmpl = new System.Windows.Forms.ComboBox();
            this.lblFileFormat = new System.Windows.Forms.Label();
            this.lblOutputDir = new System.Windows.Forms.Label();
            this.lblTemplate = new System.Windows.Forms.Label();
            this.folderBrowserOutputDirDialog = new System.Windows.Forms.FolderBrowserDialog();
            this.singleContactDataMapListView = new MailMergeAddIn.SingleContactDataMapListView();
            this.gBoxDataMap.SuspendLayout();
            this.gBoxSettings.SuspendLayout();
            this.SuspendLayout();
            // 
            // contextMenuStrip
            // 
            this.contextMenuStrip.Name = "contextMenuStrip";
            this.contextMenuStrip.Size = new System.Drawing.Size(61, 4);
            // 
            // folderBrowserTmplDialog
            // 
            this.folderBrowserTmplDialog.SelectedPath = "[networkpath]";
            this.folderBrowserTmplDialog.HelpRequest += new System.EventHandler(this.folderBrowserDialog_HelpRequest);
            // 
            // backgroundWorker
            // 
            this.backgroundWorker.WorkerReportsProgress = true;
            this.backgroundWorker.WorkerSupportsCancellation = true;
            this.backgroundWorker.DoWork += new System.ComponentModel.DoWorkEventHandler(this.backgroundWorker_DoWork);
            this.backgroundWorker.RunWorkerCompleted += new System.ComponentModel.RunWorkerCompletedEventHandler(this.backgroundWorker_RunWorkerCompleted);
            // 
            // openFileDialog
            // 
            this.openFileDialog.Filter = "Mail Merge Configuration files|*.conf";
            this.openFileDialog.Title = "Open existing configuration...";
            // 
            // saveFileDialog
            // 
            this.saveFileDialog.Filter = "Mail Merge Configuration files|*.conf";
            this.saveFileDialog.Title = "Save current configuration...";
            // 
            // BottomToolStripPanel
            // 
            this.BottomToolStripPanel.Location = new System.Drawing.Point(0, 0);
            this.BottomToolStripPanel.Name = "BottomToolStripPanel";
            this.BottomToolStripPanel.Orientation = System.Windows.Forms.Orientation.Horizontal;
            this.BottomToolStripPanel.RowMargin = new System.Windows.Forms.Padding(3, 0, 0, 0);
            this.BottomToolStripPanel.Size = new System.Drawing.Size(0, 0);
            // 
            // TopToolStripPanel
            // 
            this.TopToolStripPanel.BackColor = System.Drawing.SystemColors.Window;
            this.TopToolStripPanel.Location = new System.Drawing.Point(0, 0);
            this.TopToolStripPanel.Name = "TopToolStripPanel";
            this.TopToolStripPanel.Orientation = System.Windows.Forms.Orientation.Horizontal;
            this.TopToolStripPanel.RowMargin = new System.Windows.Forms.Padding(3, 0, 0, 0);
            this.TopToolStripPanel.Size = new System.Drawing.Size(0, 0);
            // 
            // RightToolStripPanel
            // 
            this.RightToolStripPanel.Location = new System.Drawing.Point(0, 0);
            this.RightToolStripPanel.Name = "RightToolStripPanel";
            this.RightToolStripPanel.Orientation = System.Windows.Forms.Orientation.Horizontal;
            this.RightToolStripPanel.RowMargin = new System.Windows.Forms.Padding(3, 0, 0, 0);
            this.RightToolStripPanel.Size = new System.Drawing.Size(0, 0);
            // 
            // LeftToolStripPanel
            // 
            this.LeftToolStripPanel.Location = new System.Drawing.Point(0, 0);
            this.LeftToolStripPanel.Name = "LeftToolStripPanel";
            this.LeftToolStripPanel.Orientation = System.Windows.Forms.Orientation.Horizontal;
            this.LeftToolStripPanel.RowMargin = new System.Windows.Forms.Padding(3, 0, 0, 0);
            this.LeftToolStripPanel.Size = new System.Drawing.Size(0, 0);
            // 
            // ContentPanel
            // 
            this.ContentPanel.BackColor = System.Drawing.SystemColors.Window;
            this.ContentPanel.Size = new System.Drawing.Size(484, 510);
            // 
            // btnStopMerge
            // 
            this.btnStopMerge.Location = new System.Drawing.Point(195, 523);
            this.btnStopMerge.Name = "btnStopMerge";
            this.btnStopMerge.Size = new System.Drawing.Size(75, 23);
            this.btnStopMerge.TabIndex = 13;
            this.btnStopMerge.Text = "Stop";
            this.btnStopMerge.UseVisualStyleBackColor = true;
            this.btnStopMerge.Visible = false;
            this.btnStopMerge.Click += new System.EventHandler(this.btnStopMerge_Click);
            // 
            // progressBar
            // 
            this.progressBar.Location = new System.Drawing.Point(6, 494);
            this.progressBar.Name = "progressBar";
            this.progressBar.Size = new System.Drawing.Size(452, 23);
            this.progressBar.TabIndex = 12;
            this.progressBar.Visible = false;
            // 
            // lblReportResultCount
            // 
            this.lblReportResultCount.AutoSize = true;
            this.lblReportResultCount.Location = new System.Drawing.Point(9, 470);
            this.lblReportResultCount.Name = "lblReportResultCount";
            this.lblReportResultCount.Size = new System.Drawing.Size(131, 13);
            this.lblReportResultCount.TabIndex = 11;
            this.lblReportResultCount.Text = "number of results to merge";
            this.lblReportResultCount.Visible = false;
            // 
            // btnPreview
            // 
            this.btnPreview.Enabled = false;
            this.btnPreview.Location = new System.Drawing.Point(227, 465);
            this.btnPreview.Name = "btnPreview";
            this.btnPreview.Size = new System.Drawing.Size(108, 23);
            this.btnPreview.TabIndex = 10;
            this.btnPreview.Text = "Preview";
            this.btnPreview.UseVisualStyleBackColor = true;
            this.btnPreview.Click += new System.EventHandler(this.btnPreview_Click);
            // 
            // btnMerge
            // 
            this.btnMerge.Enabled = false;
            this.btnMerge.Location = new System.Drawing.Point(341, 465);
            this.btnMerge.Name = "btnMerge";
            this.btnMerge.Size = new System.Drawing.Size(120, 23);
            this.btnMerge.TabIndex = 9;
            this.btnMerge.Text = "Run Mail Merge";
            this.btnMerge.UseVisualStyleBackColor = true;
            this.btnMerge.Click += new System.EventHandler(this.btnMerge_Click);
            // 
            // gBoxDataMap
            // 
            this.gBoxDataMap.Controls.Add(this.singleContactDataMapListView);
            this.gBoxDataMap.Location = new System.Drawing.Point(3, 211);
            this.gBoxDataMap.Name = "gBoxDataMap";
            this.gBoxDataMap.Size = new System.Drawing.Size(458, 222);
            this.gBoxDataMap.TabIndex = 8;
            this.gBoxDataMap.TabStop = false;
            this.gBoxDataMap.Text = "Data Map";
            // 
            // gBoxSettings
            // 
            this.gBoxSettings.Controls.Add(this.lblLoad);
            this.gBoxSettings.Controls.Add(this.lblFileExt);
            this.gBoxSettings.Controls.Add(this.lblSave);
            this.gBoxSettings.Controls.Add(this.lblContactFullName);
            this.gBoxSettings.Controls.Add(this.cBoxPdf);
            this.gBoxSettings.Controls.Add(this.btnOpen);
            this.gBoxSettings.Controls.Add(this.btnSave);
            this.gBoxSettings.Controls.Add(this.btnBrowseOutputDir);
            this.gBoxSettings.Controls.Add(this.btnBrowseTmpl);
            this.gBoxSettings.Controls.Add(this.cboxSendToPrinter);
            this.gBoxSettings.Controls.Add(this.cboxAttachToContact);
            this.gBoxSettings.Controls.Add(this.txtFileFormat);
            this.gBoxSettings.Controls.Add(this.txtOutputDir);
            this.gBoxSettings.Controls.Add(this.cmboBoxTmpl);
            this.gBoxSettings.Controls.Add(this.lblFileFormat);
            this.gBoxSettings.Controls.Add(this.lblOutputDir);
            this.gBoxSettings.Controls.Add(this.lblTemplate);
            this.gBoxSettings.Location = new System.Drawing.Point(3, 3);
            this.gBoxSettings.Name = "gBoxSettings";
            this.gBoxSettings.Size = new System.Drawing.Size(458, 202);
            this.gBoxSettings.TabIndex = 7;
            this.gBoxSettings.TabStop = false;
            this.gBoxSettings.Text = "Settings";
            // 
            // lblLoad
            // 
            this.lblLoad.Location = new System.Drawing.Point(299, 167);
            this.lblLoad.Name = "lblLoad";
            this.lblLoad.Size = new System.Drawing.Size(40, 13);
            this.lblLoad.TabIndex = 22;
            this.lblLoad.Text = "Load";
            this.lblLoad.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            this.lblLoad.Visible = false;
            // 
            // lblFileExt
            // 
            this.lblFileExt.AutoSize = true;
            this.lblFileExt.Location = new System.Drawing.Point(342, 75);
            this.lblFileExt.Name = "lblFileExt";
            this.lblFileExt.Size = new System.Drawing.Size(33, 13);
            this.lblFileExt.TabIndex = 19;
            this.lblFileExt.Text = ".docx";
            // 
            // lblSave
            // 
            this.lblSave.Location = new System.Drawing.Point(345, 167);
            this.lblSave.Name = "lblSave";
            this.lblSave.Size = new System.Drawing.Size(40, 13);
            this.lblSave.TabIndex = 21;
            this.lblSave.Text = "Save";
            this.lblSave.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            this.lblSave.Visible = false;
            // 
            // lblContactFullName
            // 
            this.lblContactFullName.Location = new System.Drawing.Point(96, 95);
            this.lblContactFullName.Name = "lblContactFullName";
            this.lblContactFullName.Size = new System.Drawing.Size(243, 13);
            this.lblContactFullName.TabIndex = 18;
            this.lblContactFullName.Text = "Contact Full Name";
            this.lblContactFullName.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            this.lblContactFullName.Click += new System.EventHandler(this.lblContactFullName_Click);
            // 
            // cBoxPdf
            // 
            this.cBoxPdf.AutoSize = true;
            this.cBoxPdf.Enabled = false;
            this.cBoxPdf.Location = new System.Drawing.Point(96, 170);
            this.cBoxPdf.Name = "cBoxPdf";
            this.cBoxPdf.Size = new System.Drawing.Size(79, 17);
            this.cBoxPdf.TabIndex = 18;
            this.cBoxPdf.Text = "PDF format";
            this.cBoxPdf.UseVisualStyleBackColor = true;
            this.cBoxPdf.CheckedChanged += new System.EventHandler(this.cBoxPdf_CheckedChanged);
            // 
            // btnOpen
            // 
            this.btnOpen.Image = global::MailMergeAddIn.Properties.Resources.OpenButton;
            this.btnOpen.Location = new System.Drawing.Point(299, 124);
            this.btnOpen.Name = "btnOpen";
            this.btnOpen.Size = new System.Drawing.Size(40, 40);
            this.btnOpen.TabIndex = 20;
            this.btnOpen.UseVisualStyleBackColor = true;
            this.btnOpen.Visible = false;
            this.btnOpen.Click += new System.EventHandler(this.btnOpen_Click);
            // 
            // btnSave
            // 
            this.btnSave.Image = global::MailMergeAddIn.Properties.Resources.SaveButton;
            this.btnSave.Location = new System.Drawing.Point(345, 124);
            this.btnSave.Name = "btnSave";
            this.btnSave.Size = new System.Drawing.Size(40, 40);
            this.btnSave.TabIndex = 14;
            this.btnSave.UseVisualStyleBackColor = true;
            this.btnSave.Visible = false;
            this.btnSave.Click += new System.EventHandler(this.btnSave_Click);
            // 
            // btnBrowseOutputDir
            // 
            this.btnBrowseOutputDir.Enabled = false;
            this.btnBrowseOutputDir.Location = new System.Drawing.Point(345, 44);
            this.btnBrowseOutputDir.Name = "btnBrowseOutputDir";
            this.btnBrowseOutputDir.Size = new System.Drawing.Size(50, 23);
            this.btnBrowseOutputDir.TabIndex = 17;
            this.btnBrowseOutputDir.Text = "Browse";
            this.btnBrowseOutputDir.UseVisualStyleBackColor = true;
            this.btnBrowseOutputDir.Click += new System.EventHandler(this.btnBrowseOutputDir_Click);
            // 
            // btnBrowseTmpl
            // 
            this.btnBrowseTmpl.Location = new System.Drawing.Point(345, 17);
            this.btnBrowseTmpl.Name = "btnBrowseTmpl";
            this.btnBrowseTmpl.Size = new System.Drawing.Size(50, 23);
            this.btnBrowseTmpl.TabIndex = 16;
            this.btnBrowseTmpl.Text = "Browse";
            this.btnBrowseTmpl.UseVisualStyleBackColor = true;
            this.btnBrowseTmpl.Click += new System.EventHandler(this.btnBrowseTmpl_Click);
            // 
            // cboxSendToPrinter
            // 
            this.cboxSendToPrinter.AutoSize = true;
            this.cboxSendToPrinter.Enabled = false;
            this.cboxSendToPrinter.Location = new System.Drawing.Point(96, 147);
            this.cboxSendToPrinter.Name = "cboxSendToPrinter";
            this.cboxSendToPrinter.Size = new System.Drawing.Size(129, 17);
            this.cboxSendToPrinter.TabIndex = 13;
            this.cboxSendToPrinter.Text = "Print on default printer";
            this.cboxSendToPrinter.UseVisualStyleBackColor = true;
            // 
            // cboxAttachToContact
            // 
            this.cboxAttachToContact.AutoSize = true;
            this.cboxAttachToContact.Enabled = true;
            this.cboxAttachToContact.Location = new System.Drawing.Point(96, 124);
            this.cboxAttachToContact.Name = "cboxAttachToContact";
            this.cboxAttachToContact.Size = new System.Drawing.Size(152, 17);
            this.cboxAttachToContact.TabIndex = 11;
            this.cboxAttachToContact.Text = "Attach document to record";
            this.cboxAttachToContact.UseVisualStyleBackColor = true;
            // 
            // txtFileFormat
            // 
            this.txtFileFormat.ContextMenuStrip = this.contextMenuStrip;
            this.txtFileFormat.Enabled = false;
            this.txtFileFormat.Location = new System.Drawing.Point(96, 72);
            this.txtFileFormat.Name = "txtFileFormat";
            this.txtFileFormat.Size = new System.Drawing.Size(243, 20);
            this.txtFileFormat.TabIndex = 8;
            // 
            // txtOutputDir
            // 
            this.txtOutputDir.Enabled = false;
            this.txtOutputDir.Location = new System.Drawing.Point(96, 46);
            this.txtOutputDir.Name = "txtOutputDir";
            this.txtOutputDir.Size = new System.Drawing.Size(243, 20);
            this.txtOutputDir.TabIndex = 6;
            // 
            // cmboBoxTmpl
            // 
            this.cmboBoxTmpl.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmboBoxTmpl.FormattingEnabled = true;
            this.cmboBoxTmpl.Location = new System.Drawing.Point(96, 19);
            this.cmboBoxTmpl.Name = "cmboBoxTmpl";
            this.cmboBoxTmpl.Size = new System.Drawing.Size(243, 21);
            this.cmboBoxTmpl.TabIndex = 5;
            this.cmboBoxTmpl.SelectedIndexChanged += new System.EventHandler(this.cmboBoxTmpl_SelectedIndexChanged);
            // 
            // lblFileFormat
            // 
            this.lblFileFormat.AutoSize = true;
            this.lblFileFormat.Enabled = false;
            this.lblFileFormat.Location = new System.Drawing.Point(6, 75);
            this.lblFileFormat.Name = "lblFileFormat";
            this.lblFileFormat.Size = new System.Drawing.Size(84, 13);
            this.lblFileFormat.TabIndex = 2;
            this.lblFileFormat.Text = "Filename Format";
            // 
            // lblOutputDir
            // 
            this.lblOutputDir.AutoSize = true;
            this.lblOutputDir.Enabled = false;
            this.lblOutputDir.Location = new System.Drawing.Point(6, 49);
            this.lblOutputDir.Name = "lblOutputDir";
            this.lblOutputDir.Size = new System.Drawing.Size(84, 13);
            this.lblOutputDir.TabIndex = 1;
            this.lblOutputDir.Text = "Output Directory";
            // 
            // lblTemplate
            // 
            this.lblTemplate.AutoSize = true;
            this.lblTemplate.Location = new System.Drawing.Point(39, 22);
            this.lblTemplate.Name = "lblTemplate";
            this.lblTemplate.Size = new System.Drawing.Size(51, 13);
            this.lblTemplate.TabIndex = 0;
            this.lblTemplate.Text = "Template";
            // 
            // singleContactDataMapListView
            // 
            this.singleContactDataMapListView.ContactFieldDataSource = ((System.Collections.Generic.List<string>)(resources.GetObject("singleContactDataMapListView.ContactFieldDataSource")));
            this.singleContactDataMapListView.ContactFieldDataSourceDict = null;
            this.singleContactDataMapListView.FullRowSelect = true;
            this.singleContactDataMapListView.GridLines = true;
            this.singleContactDataMapListView.Location = new System.Drawing.Point(6, 19);
            this.singleContactDataMapListView.Name = "singleContactDataMapListView";
            this.singleContactDataMapListView.Size = new System.Drawing.Size(446, 197);
            this.singleContactDataMapListView.TabIndex = 0;
            this.singleContactDataMapListView.UseCompatibleStateImageBehavior = false;
            this.singleContactDataMapListView.View = System.Windows.Forms.View.Details;
            this.singleContactDataMapListView.SelectedIndexChanged += new System.EventHandler(this.singleContactDataMapListView_SelectedIndexChanged);
            // 
            // SingleContactMailMergeControl
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.AutoSize = true;
            this.BackColor = System.Drawing.Color.White;
            this.Controls.Add(this.gBoxSettings);
            this.Controls.Add(this.progressBar);
            this.Controls.Add(this.btnStopMerge);
            this.Controls.Add(this.btnMerge);
            this.Controls.Add(this.lblReportResultCount);
            this.Controls.Add(this.btnPreview);
            this.Controls.Add(this.gBoxDataMap);
            this.Name = "SingleContactMailMergeControl";
            this.Size = new System.Drawing.Size(470, 549);
            this.gBoxDataMap.ResumeLayout(false);
            this.gBoxSettings.ResumeLayout(false);
            this.gBoxSettings.PerformLayout();
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.FolderBrowserDialog folderBrowserTmplDialog;
        private System.ComponentModel.BackgroundWorker backgroundWorker;
        private System.Windows.Forms.ContextMenuStrip contextMenuStrip;
        private System.Windows.Forms.OpenFileDialog openFileDialog;
        private System.Windows.Forms.SaveFileDialog saveFileDialog;
        private System.Windows.Forms.ToolStripPanel BottomToolStripPanel;
        private System.Windows.Forms.ToolStripPanel TopToolStripPanel;
        private System.Windows.Forms.ToolStripPanel RightToolStripPanel;
        private System.Windows.Forms.ToolStripPanel LeftToolStripPanel;
        private System.Windows.Forms.ToolStripContentPanel ContentPanel;
        private System.Windows.Forms.Button btnStopMerge;
        private System.Windows.Forms.ProgressBar progressBar;
        private System.Windows.Forms.Label lblReportResultCount;
        private System.Windows.Forms.Button btnPreview;
        private System.Windows.Forms.Button btnMerge;
        private System.Windows.Forms.GroupBox gBoxDataMap;
        private System.Windows.Forms.GroupBox gBoxSettings;
        private System.Windows.Forms.Button btnBrowseOutputDir;
        private System.Windows.Forms.Button btnBrowseTmpl;
        private System.Windows.Forms.CheckBox cboxSendToPrinter;
        private System.Windows.Forms.CheckBox cboxAttachToContact;
        private System.Windows.Forms.TextBox txtFileFormat;
        private System.Windows.Forms.TextBox txtOutputDir;
        private System.Windows.Forms.ComboBox cmboBoxTmpl;
        private System.Windows.Forms.Label lblFileFormat;
        private System.Windows.Forms.Label lblOutputDir;
        private System.Windows.Forms.Label lblTemplate;
        private System.Windows.Forms.CheckBox cBoxPdf;
        private System.Windows.Forms.Label lblContactFullName;
        private System.Windows.Forms.Label lblFileExt;
        private System.Windows.Forms.Button btnOpen;
        private System.Windows.Forms.Button btnSave;
        private System.Windows.Forms.Label lblSave;
        private System.Windows.Forms.Label lblLoad;
        private System.Windows.Forms.FolderBrowserDialog folderBrowserOutputDirDialog;
        private SingleContactDataMapListView singleContactDataMapListView;
    }
}
