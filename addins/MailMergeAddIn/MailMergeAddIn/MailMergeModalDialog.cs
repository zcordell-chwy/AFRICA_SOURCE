using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Windows.Forms;
using RightNow.AddIns.AddInViews;
namespace MailMergeAddIn
{
    public partial class MailMergeModalDialog : Form
    {
        private IGlobalContext _context;
        private string _defaultTemplDir;
        private string _templateCustomField;

        public MailMergeModalDialog(System.Collections.Generic.List<System.String> contactProperties, System.Collections.Generic.List<System.String> contactValues)
        {
            InitializeComponent();

            this.singleContactMailMergeControl1 = new MailMergeAddIn.SingleContactMailMergeControl(contactProperties, contactValues);
            this.SuspendLayout();
            // 
            // singleContactMailMergeControl1
            // 
            this.singleContactMailMergeControl1.Location = new System.Drawing.Point(10, 12);
            this.singleContactMailMergeControl1.MergeInProgressErrorStr = null;
            this.singleContactMailMergeControl1.Name = "singleContactMailMergeControl1";
            this.singleContactMailMergeControl1.Size = new System.Drawing.Size(470, 505);
            this.singleContactMailMergeControl1.TabIndex = 0;
            // 
            // MailMergeModalDialog
            // 
            this.AutoSize = true;
            this.ClientSize = new System.Drawing.Size(492, 521);
            this.Controls.Add(this.singleContactMailMergeControl1);
            this.Name = "MailMergeModalDialog";
            this.Text = "Mail Merge";
            this.Icon = Icon.FromHandle(Properties.Resources.MailMerge64.GetHicon());
            this.ResumeLayout(false);
        }

        /**
         * testing
         */
        public MailMergeModalDialog(SortedDictionary<string, string> recordData, IGlobalContext context, string defaultTemplDir, string templateCustomField)
        {
            this._context = context;
            this._defaultTemplDir = defaultTemplDir;
            this._templateCustomField = templateCustomField;
            InitializeComponent();

            this.singleContactMailMergeControl1 = new MailMergeAddIn.SingleContactMailMergeControl(recordData, this._context, this._defaultTemplDir,this._templateCustomField);
            this.SuspendLayout();
            // 
            // singleContactMailMergeControl1
            // 
            this.singleContactMailMergeControl1.Location = new System.Drawing.Point(10, 12);
            this.singleContactMailMergeControl1.MergeInProgressErrorStr = null;
            this.singleContactMailMergeControl1.Name = "singleContactMailMergeControl1";
            this.singleContactMailMergeControl1.Size = new System.Drawing.Size(470, 505);
            this.singleContactMailMergeControl1.TabIndex = 0;
            // 
            // MailMergeModalDialog
            // 
            this.AutoSize = true;
            this.ClientSize = new System.Drawing.Size(492, 521);
            this.Controls.Add(this.singleContactMailMergeControl1);
            this.Name = "MailMergeModalDialog";
            this.Text = "Mail Merge";
            this.Icon = Icon.FromHandle(Properties.Resources.MailMerge64.GetHicon());
            this.ResumeLayout(false);
        }

        private SingleContactMailMergeControl singleContactMailMergeControl1;
    }
}
