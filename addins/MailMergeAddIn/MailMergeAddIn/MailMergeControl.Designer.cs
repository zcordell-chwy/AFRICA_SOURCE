namespace MailMergeAddIn
{
    partial class MailMergeControl
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(MailMergeControl));
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
            this.txtBoxReportId = new System.Windows.Forms.TextBox();
            this.btnPullReport = new System.Windows.Forms.Button();
            this.btnBrowseOutputDir = new System.Windows.Forms.Button();
            this.btnBrowseTmpl = new System.Windows.Forms.Button();
            this.cboxMergeSingleDoc = new System.Windows.Forms.CheckBox();
            this.cboxAttachToContact = new System.Windows.Forms.CheckBox();
            this.lblFileExt = new System.Windows.Forms.Label();
            this.txtFileFormat = new System.Windows.Forms.TextBox();
            this.txtOutputDir = new System.Windows.Forms.TextBox();
            this.cmboBoxTmpl = new System.Windows.Forms.ComboBox();
            this.lblReport = new System.Windows.Forms.Label();
            this.lblFileFormat = new System.Windows.Forms.Label();
            this.lblOutputDir = new System.Windows.Forms.Label();
            this.lblTemplate = new System.Windows.Forms.Label();
            this.folderBrowserOutputDirDialog = new System.Windows.Forms.FolderBrowserDialog();
            this.tableLayoutPanel1 = new System.Windows.Forms.TableLayoutPanel();
            this.cboxSendToPrinter = new System.Windows.Forms.CheckBox();
            this.endIdxText = new System.Windows.Forms.TextBox();
            this.label3 = new System.Windows.Forms.Label();
            this.startIdxText = new System.Windows.Forms.TextBox();
            this.donationDataReportId = new System.Windows.Forms.TextBox();
            this.label1 = new System.Windows.Forms.Label();
            this.label2 = new System.Windows.Forms.Label();
            this.contactIsID = new System.Windows.Forms.CheckBox();
            this.cBoxPdf = new System.Windows.Forms.CheckBox();
            this.gBoxSettings = new System.Windows.Forms.GroupBox();
            this.cboxEmail = new System.Windows.Forms.CheckBox();
            this.dataMapListView = new MailMergeAddIn.DataMapListView();
            this.gBoxDataMap.SuspendLayout();
            this.tableLayoutPanel1.SuspendLayout();
            this.gBoxSettings.SuspendLayout();
            this.SuspendLayout();
            // 
            // contextMenuStrip
            // 
            this.contextMenuStrip.ImageScalingSize = new System.Drawing.Size(20, 20);
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
            this.btnStopMerge.Location = new System.Drawing.Point(249, 583);
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
            this.progressBar.Location = new System.Drawing.Point(60, 554);
            this.progressBar.Name = "progressBar";
            this.progressBar.Size = new System.Drawing.Size(452, 23);
            this.progressBar.TabIndex = 12;
            this.progressBar.Visible = false;
            // 
            // lblReportResultCount
            // 
            this.lblReportResultCount.AutoSize = true;
            this.lblReportResultCount.Location = new System.Drawing.Point(63, 530);
            this.lblReportResultCount.Name = "lblReportResultCount";
            this.lblReportResultCount.Size = new System.Drawing.Size(131, 13);
            this.lblReportResultCount.TabIndex = 11;
            this.lblReportResultCount.Text = "number of results to merge";
            this.lblReportResultCount.Visible = false;
            // 
            // btnPreview
            // 
            this.btnPreview.Enabled = false;
            this.btnPreview.Location = new System.Drawing.Point(281, 525);
            this.btnPreview.Name = "btnPreview";
            this.btnPreview.Size = new System.Drawing.Size(108, 23);
            this.btnPreview.TabIndex = 10;
            this.btnPreview.Text = "Preview Mail Merge";
            this.btnPreview.UseVisualStyleBackColor = true;
            this.btnPreview.Click += new System.EventHandler(this.btnPreview_Click);
            // 
            // btnMerge
            // 
            this.btnMerge.Enabled = false;
            this.btnMerge.Location = new System.Drawing.Point(395, 525);
            this.btnMerge.Name = "btnMerge";
            this.btnMerge.Size = new System.Drawing.Size(120, 23);
            this.btnMerge.TabIndex = 9;
            this.btnMerge.Text = "Run Mail Merge";
            this.btnMerge.UseVisualStyleBackColor = true;
            this.btnMerge.Click += new System.EventHandler(this.btnMerge_Click);
            // 
            // gBoxDataMap
            // 
            this.gBoxDataMap.Controls.Add(this.dataMapListView);
            this.gBoxDataMap.Location = new System.Drawing.Point(43, 337);
            this.gBoxDataMap.Name = "gBoxDataMap";
            this.gBoxDataMap.Size = new System.Drawing.Size(584, 177);
            this.gBoxDataMap.TabIndex = 8;
            this.gBoxDataMap.TabStop = false;
            this.gBoxDataMap.Text = "Data Map";
            // 
            // txtBoxReportId
            // 
            this.txtBoxReportId.AcceptsReturn = true;
            this.txtBoxReportId.AcceptsTab = true;
            this.txtBoxReportId.Location = new System.Drawing.Point(131, 86);
            this.txtBoxReportId.Margin = new System.Windows.Forms.Padding(2);
            this.txtBoxReportId.MaxLength = 12;
            this.txtBoxReportId.Name = "txtBoxReportId";
            this.txtBoxReportId.Size = new System.Drawing.Size(324, 20);
            this.txtBoxReportId.TabIndex = 19;
            this.txtBoxReportId.Text = "100541";
            // 
            // btnPullReport
            // 
            this.btnPullReport.Location = new System.Drawing.Point(461, 87);
            this.btnPullReport.Name = "btnPullReport";
            this.btnPullReport.Size = new System.Drawing.Size(87, 23);
            this.btnPullReport.TabIndex = 18;
            this.btnPullReport.Text = "Pull Report";
            this.btnPullReport.UseVisualStyleBackColor = true;
            this.btnPullReport.Click += new System.EventHandler(this.btnPullReport_Click);
            // 
            // btnBrowseOutputDir
            // 
            this.btnBrowseOutputDir.Enabled = false;
            this.btnBrowseOutputDir.Location = new System.Drawing.Point(461, 32);
            this.btnBrowseOutputDir.Name = "btnBrowseOutputDir";
            this.btnBrowseOutputDir.Size = new System.Drawing.Size(50, 23);
            this.btnBrowseOutputDir.TabIndex = 17;
            this.btnBrowseOutputDir.Text = "Browse";
            this.btnBrowseOutputDir.UseVisualStyleBackColor = true;
            this.btnBrowseOutputDir.Click += new System.EventHandler(this.btnBrowseOutputDir_Click);
            // 
            // btnBrowseTmpl
            // 
            this.btnBrowseTmpl.Location = new System.Drawing.Point(461, 3);
            this.btnBrowseTmpl.Name = "btnBrowseTmpl";
            this.btnBrowseTmpl.Size = new System.Drawing.Size(50, 23);
            this.btnBrowseTmpl.TabIndex = 16;
            this.btnBrowseTmpl.Text = "Browse";
            this.btnBrowseTmpl.UseVisualStyleBackColor = true;
            this.btnBrowseTmpl.Click += new System.EventHandler(this.btnBrowseTmpl_Click);
            // 
            // cboxMergeSingleDoc
            // 
            this.cboxMergeSingleDoc.AutoSize = true;
            this.cboxMergeSingleDoc.Checked = true;
            this.cboxMergeSingleDoc.CheckState = System.Windows.Forms.CheckState.Checked;
            this.cboxMergeSingleDoc.Enabled = false;
            this.cboxMergeSingleDoc.Location = new System.Drawing.Point(132, 235);
            this.cboxMergeSingleDoc.Name = "cboxMergeSingleDoc";
            this.cboxMergeSingleDoc.Size = new System.Drawing.Size(211, 14);
            this.cboxMergeSingleDoc.TabIndex = 12;
            this.cboxMergeSingleDoc.Text = "Merge all results into a single document";
            this.cboxMergeSingleDoc.UseVisualStyleBackColor = true;
            this.cboxMergeSingleDoc.CheckedChanged += new System.EventHandler(this.cboxMergeSingleDoc_CheckedChanged);
            // 
            // cboxAttachToContact
            // 
            this.cboxAttachToContact.AutoSize = true;
            this.cboxAttachToContact.Enabled = false;
            this.cboxAttachToContact.Location = new System.Drawing.Point(132, 215);
            this.cboxAttachToContact.Name = "cboxAttachToContact";
            this.cboxAttachToContact.Size = new System.Drawing.Size(323, 14);
            this.cboxAttachToContact.TabIndex = 11;
            this.cboxAttachToContact.Text = "Attach each merged document to its associated contact record";
            this.cboxAttachToContact.UseVisualStyleBackColor = true;
            // 
            // lblFileExt
            // 
            this.lblFileExt.Anchor = System.Windows.Forms.AnchorStyles.Left;
            this.lblFileExt.AutoSize = true;
            this.lblFileExt.Enabled = false;
            this.lblFileExt.Location = new System.Drawing.Point(461, 64);
            this.lblFileExt.Name = "lblFileExt";
            this.lblFileExt.Size = new System.Drawing.Size(33, 13);
            this.lblFileExt.TabIndex = 9;
            this.lblFileExt.Text = ".docx";
            // 
            // txtFileFormat
            // 
            this.txtFileFormat.ContextMenuStrip = this.contextMenuStrip;
            this.txtFileFormat.Enabled = false;
            this.txtFileFormat.Location = new System.Drawing.Point(132, 61);
            this.txtFileFormat.Name = "txtFileFormat";
            this.txtFileFormat.Size = new System.Drawing.Size(323, 20);
            this.txtFileFormat.TabIndex = 8;
            // 
            // txtOutputDir
            // 
            this.txtOutputDir.Enabled = false;
            this.txtOutputDir.Location = new System.Drawing.Point(132, 32);
            this.txtOutputDir.Name = "txtOutputDir";
            this.txtOutputDir.Size = new System.Drawing.Size(323, 20);
            this.txtOutputDir.TabIndex = 6;
            // 
            // cmboBoxTmpl
            // 
            this.cmboBoxTmpl.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.cmboBoxTmpl.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmboBoxTmpl.FormattingEnabled = true;
            this.cmboBoxTmpl.Location = new System.Drawing.Point(132, 3);
            this.cmboBoxTmpl.Name = "cmboBoxTmpl";
            this.cmboBoxTmpl.Size = new System.Drawing.Size(323, 21);
            this.cmboBoxTmpl.TabIndex = 5;
            this.cmboBoxTmpl.SelectedIndexChanged += new System.EventHandler(this.cmboBoxTmpl_SelectedIndexChanged);
            // 
            // lblReport
            // 
            this.lblReport.AutoSize = true;
            this.lblReport.Enabled = false;
            this.lblReport.Location = new System.Drawing.Point(3, 84);
            this.lblReport.Name = "lblReport";
            this.lblReport.Size = new System.Drawing.Size(96, 13);
            this.lblReport.TabIndex = 3;
            this.lblReport.Text = "Analytics Report Id";
            // 
            // lblFileFormat
            // 
            this.lblFileFormat.AutoSize = true;
            this.lblFileFormat.Enabled = false;
            this.lblFileFormat.Location = new System.Drawing.Point(3, 58);
            this.lblFileFormat.Name = "lblFileFormat";
            this.lblFileFormat.Size = new System.Drawing.Size(84, 13);
            this.lblFileFormat.TabIndex = 2;
            this.lblFileFormat.Text = "Filename Format";
            // 
            // lblOutputDir
            // 
            this.lblOutputDir.AutoSize = true;
            this.lblOutputDir.Enabled = false;
            this.lblOutputDir.Location = new System.Drawing.Point(3, 29);
            this.lblOutputDir.Name = "lblOutputDir";
            this.lblOutputDir.Size = new System.Drawing.Size(84, 13);
            this.lblOutputDir.TabIndex = 1;
            this.lblOutputDir.Text = "Output Directory";
            // 
            // lblTemplate
            // 
            this.lblTemplate.AutoSize = true;
            this.lblTemplate.Location = new System.Drawing.Point(3, 0);
            this.lblTemplate.Name = "lblTemplate";
            this.lblTemplate.Size = new System.Drawing.Size(51, 13);
            this.lblTemplate.TabIndex = 0;
            this.lblTemplate.Text = "Template";
            // 
            // tableLayoutPanel1
            // 
            this.tableLayoutPanel1.Anchor = System.Windows.Forms.AnchorStyles.Left;
            this.tableLayoutPanel1.ColumnCount = 3;
            this.tableLayoutPanel1.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle());
            this.tableLayoutPanel1.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle());
            this.tableLayoutPanel1.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle());
            this.tableLayoutPanel1.Controls.Add(this.btnPullReport, 2, 3);
            this.tableLayoutPanel1.Controls.Add(this.txtBoxReportId, 1, 3);
            this.tableLayoutPanel1.Controls.Add(this.cmboBoxTmpl, 1, 0);
            this.tableLayoutPanel1.Controls.Add(this.btnBrowseOutputDir, 2, 1);
            this.tableLayoutPanel1.Controls.Add(this.btnBrowseTmpl, 2, 0);
            this.tableLayoutPanel1.Controls.Add(this.lblOutputDir, 0, 1);
            this.tableLayoutPanel1.Controls.Add(this.lblReport, 0, 3);
            this.tableLayoutPanel1.Controls.Add(this.txtOutputDir, 1, 1);
            this.tableLayoutPanel1.Controls.Add(this.txtFileFormat, 1, 2);
            this.tableLayoutPanel1.Controls.Add(this.lblFileFormat, 0, 2);
            this.tableLayoutPanel1.Controls.Add(this.lblFileExt, 2, 2);
            this.tableLayoutPanel1.Controls.Add(this.cboxSendToPrinter, 1, 11);
            this.tableLayoutPanel1.Controls.Add(this.cboxMergeSingleDoc, 1, 10);
            this.tableLayoutPanel1.Controls.Add(this.endIdxText, 1, 6);
            this.tableLayoutPanel1.Controls.Add(this.label3, 0, 6);
            this.tableLayoutPanel1.Controls.Add(this.startIdxText, 1, 5);
            this.tableLayoutPanel1.Controls.Add(this.donationDataReportId, 1, 4);
            this.tableLayoutPanel1.Controls.Add(this.label1, 0, 4);
            this.tableLayoutPanel1.Controls.Add(this.label2, 0, 5);
            this.tableLayoutPanel1.Controls.Add(this.contactIsID, 1, 7);
            this.tableLayoutPanel1.Controls.Add(this.cboxAttachToContact, 1, 9);
            this.tableLayoutPanel1.Controls.Add(this.lblTemplate, 0, 0);
            this.tableLayoutPanel1.Controls.Add(this.cBoxPdf, 2, 4);
            this.tableLayoutPanel1.Controls.Add(this.cboxEmail, 1, 12);
            this.tableLayoutPanel1.Location = new System.Drawing.Point(45, 14);
            this.tableLayoutPanel1.Name = "tableLayoutPanel1";
            this.tableLayoutPanel1.RowCount = 14;
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Absolute, 20F));
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Absolute, 20F));
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Absolute, 20F));
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Absolute, 20F));
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Absolute, 20F));
            this.tableLayoutPanel1.Size = new System.Drawing.Size(570, 316);
            this.tableLayoutPanel1.TabIndex = 19;
            this.tableLayoutPanel1.Paint += new System.Windows.Forms.PaintEventHandler(this.tableLayoutPanel1_Paint);
            // 
            // cboxSendToPrinter
            // 
            this.cboxSendToPrinter.AutoSize = true;
            this.cboxSendToPrinter.Location = new System.Drawing.Point(132, 255);
            this.cboxSendToPrinter.Name = "cboxSendToPrinter";
            this.cboxSendToPrinter.Size = new System.Drawing.Size(218, 17);
            this.cboxSendToPrinter.TabIndex = 13;
            this.cboxSendToPrinter.Text = "Send merged document to default printer";
            this.cboxSendToPrinter.UseVisualStyleBackColor = true;
            // 
            // endIdxText
            // 
            this.endIdxText.Location = new System.Drawing.Point(132, 166);
            this.endIdxText.Name = "endIdxText";
            this.endIdxText.Size = new System.Drawing.Size(323, 20);
            this.endIdxText.TabIndex = 27;
            this.endIdxText.Text = "5";
            // 
            // label3
            // 
            this.label3.AutoSize = true;
            this.label3.Enabled = false;
            this.label3.Location = new System.Drawing.Point(3, 163);
            this.label3.Name = "label3";
            this.label3.Size = new System.Drawing.Size(55, 13);
            this.label3.TabIndex = 26;
            this.label3.Text = "End Index";
            // 
            // startIdxText
            // 
            this.startIdxText.Location = new System.Drawing.Point(132, 140);
            this.startIdxText.Name = "startIdxText";
            this.startIdxText.Size = new System.Drawing.Size(323, 20);
            this.startIdxText.TabIndex = 25;
            this.startIdxText.Text = "0";
            // 
            // donationDataReportId
            // 
            this.donationDataReportId.AcceptsReturn = true;
            this.donationDataReportId.AcceptsTab = true;
            this.donationDataReportId.Location = new System.Drawing.Point(131, 115);
            this.donationDataReportId.Margin = new System.Windows.Forms.Padding(2);
            this.donationDataReportId.MaxLength = 12;
            this.donationDataReportId.Name = "donationDataReportId";
            this.donationDataReportId.Size = new System.Drawing.Size(324, 20);
            this.donationDataReportId.TabIndex = 22;
            this.donationDataReportId.Text = "100542";
            // 
            // label1
            // 
            this.label1.AutoSize = true;
            this.label1.Enabled = false;
            this.label1.Location = new System.Drawing.Point(3, 113);
            this.label1.Name = "label1";
            this.label1.Size = new System.Drawing.Size(123, 13);
            this.label1.TabIndex = 23;
            this.label1.Text = "Donation Data Report Id";
            // 
            // label2
            // 
            this.label2.AutoSize = true;
            this.label2.Enabled = false;
            this.label2.Location = new System.Drawing.Point(3, 137);
            this.label2.Name = "label2";
            this.label2.Size = new System.Drawing.Size(58, 13);
            this.label2.TabIndex = 24;
            this.label2.Text = "Start Index";
            // 
            // contactIsID
            // 
            this.contactIsID.AutoSize = true;
            this.contactIsID.Checked = true;
            this.contactIsID.CheckState = System.Windows.Forms.CheckState.Checked;
            this.contactIsID.Location = new System.Drawing.Point(132, 192);
            this.contactIsID.Name = "contactIsID";
            this.contactIsID.Size = new System.Drawing.Size(198, 17);
            this.contactIsID.TabIndex = 28;
            this.contactIsID.Text = "Contact Id Contained in \"ID\" column";
            this.contactIsID.UseVisualStyleBackColor = true;
            this.contactIsID.CheckedChanged += new System.EventHandler(this.contactIsID_CheckedChanged);
            // 
            // cBoxPdf
            // 
            this.cBoxPdf.AutoSize = true;
            this.cBoxPdf.Checked = true;
            this.cBoxPdf.CheckState = System.Windows.Forms.CheckState.Checked;
            this.cBoxPdf.Enabled = false;
            this.cBoxPdf.Location = new System.Drawing.Point(132, 278);
            this.cBoxPdf.Name = "cBoxPdf";
            this.cBoxPdf.Size = new System.Drawing.Size(157, 14);
            this.cBoxPdf.TabIndex = 18;
            this.cBoxPdf.Text = "Create output in PDF format";
            this.cBoxPdf.UseVisualStyleBackColor = true;
            this.cBoxPdf.Visible = false;
            this.cBoxPdf.CheckedChanged += new System.EventHandler(this.cBoxPdf_CheckedChanged);
            // 
            // gBoxSettings
            // 
            this.gBoxSettings.Controls.Add(this.tableLayoutPanel1);
            this.gBoxSettings.Location = new System.Drawing.Point(-8, 3);
            this.gBoxSettings.Name = "gBoxSettings";
            this.gBoxSettings.Size = new System.Drawing.Size(595, 328);
            this.gBoxSettings.TabIndex = 7;
            this.gBoxSettings.TabStop = false;
            this.gBoxSettings.Text = "Settings";
            // 
            // cboxEmail
            // 
            this.cboxEmail.AutoSize = true;
            this.cboxEmail.Location = new System.Drawing.Point(133, 298);
            this.cboxEmail.Name = "cboxEmail";
            this.cboxEmail.Size = new System.Drawing.Size(280, 17);
            this.cboxEmail.TabIndex = 29;
            this.cboxEmail.Text = "Email Merged File (HTML template only)";
            this.cboxEmail.UseVisualStyleBackColor = true;
            this.cboxEmail.CheckedChanged += new System.EventHandler(this.checkBox1_CheckedChanged);
            // 
            // dataMapListView
            // 
            this.dataMapListView.Dock = System.Windows.Forms.DockStyle.Fill;
            this.dataMapListView.FullRowSelect = true;
            this.dataMapListView.GridLines = true;
            this.dataMapListView.Location = new System.Drawing.Point(3, 16);
            this.dataMapListView.Name = "dataMapListView";
            this.dataMapListView.ReportFieldDataSource = ((System.Collections.Generic.List<string>)(resources.GetObject("dataMapListView.ReportFieldDataSource")));
            this.dataMapListView.Size = new System.Drawing.Size(578, 158);
            this.dataMapListView.TabIndex = 2;
            this.dataMapListView.UseCompatibleStateImageBehavior = false;
            this.dataMapListView.View = System.Windows.Forms.View.Details;
            // 
            // MailMergeControl
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.AutoSize = true;
            this.BackColor = System.Drawing.Color.White;
            this.Controls.Add(this.btnStopMerge);
            this.Controls.Add(this.progressBar);
            this.Controls.Add(this.lblReportResultCount);
            this.Controls.Add(this.btnPreview);
            this.Controls.Add(this.btnMerge);
            this.Controls.Add(this.gBoxDataMap);
            this.Controls.Add(this.gBoxSettings);
            this.Name = "MailMergeControl";
            this.Size = new System.Drawing.Size(630, 616);
            this.gBoxDataMap.ResumeLayout(false);
            this.tableLayoutPanel1.ResumeLayout(false);
            this.tableLayoutPanel1.PerformLayout();
            this.gBoxSettings.ResumeLayout(false);
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
        private DataMapListView dataMapListView;
        private System.Windows.Forms.Button btnBrowseOutputDir;
        private System.Windows.Forms.Button btnBrowseTmpl;
        private System.Windows.Forms.CheckBox cboxMergeSingleDoc;
        private System.Windows.Forms.CheckBox cboxAttachToContact;
        private System.Windows.Forms.Label lblFileExt;
        private System.Windows.Forms.TextBox txtFileFormat;
        private System.Windows.Forms.TextBox txtOutputDir;
        private System.Windows.Forms.ComboBox cmboBoxTmpl;
        private System.Windows.Forms.Label lblReport;
        private System.Windows.Forms.Label lblFileFormat;
        private System.Windows.Forms.Label lblOutputDir;
        private System.Windows.Forms.Label lblTemplate;
        private System.Windows.Forms.FolderBrowserDialog folderBrowserOutputDirDialog;
        private System.Windows.Forms.Button btnPullReport;
        public System.Windows.Forms.TextBox txtBoxReportId;
        private System.Windows.Forms.TableLayoutPanel tableLayoutPanel1;
        private System.Windows.Forms.CheckBox cboxSendToPrinter;
        private System.Windows.Forms.CheckBox cBoxPdf;
        private System.Windows.Forms.GroupBox gBoxSettings;
        public System.Windows.Forms.TextBox donationDataReportId;
        private System.Windows.Forms.Label label1;
        private System.Windows.Forms.TextBox endIdxText;
        private System.Windows.Forms.Label label3;
        private System.Windows.Forms.TextBox startIdxText;
        private System.Windows.Forms.Label label2;
        private System.Windows.Forms.CheckBox contactIsID;
        private System.Windows.Forms.CheckBox cboxEmail;
    }
}
