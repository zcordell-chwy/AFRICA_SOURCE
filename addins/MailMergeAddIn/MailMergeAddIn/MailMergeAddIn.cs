using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.AddIn;
using RightNow.AddIns.AddInViews;
using System.Windows.Forms;
using System.ComponentModel;
using RightNow.AddIns.Common;
using System.Reflection;

namespace MailMergeAddIn
{
    [AddIn("Automation Client", Version="1.0.0.0")]
    public class AutoClient : IAutomationClient
    {
        internal static AutoClient instance;

        public AutoClient()
        {
            instance = this;
        }

        #region IAutomationClient Members

        public static IAutomationContext automationContext { get; private set; }
        public static IGlobalContext globalContext { get; private set; }

        public void SetAutomationContext(IAutomationContext context)
        {
            automationContext = context;
        }

        #endregion

        #region IAddInBase Members

        public bool Initialize(IGlobalContext context)
        {
            globalContext = context;
            return true;
        }

        #endregion
    }


    [AddIn("Mail Merge Workspace Component Factory", Version="1.0.0.0")]
    public class MailMergeWorkspaceComponentFactory : IWorkspaceComponentFactory2
    {
        #region IWorkspaceComponentFactory2 Members

        [ServerConfigProperty(DefaultValue = "C:\\templates")]
        public String DefaultTemplateDirectory //TODO:Determine  how to validate the server config value is working correctly
        {
            get { return @"C:\templates"; }
            //set { this.DefaultTemplateDirectory = value; }
        }

        [ServerConfigProperty(DefaultValue = "Incident: c$loadmailmergetemplate")]
        public String templateCustomField
        {
            get;
            set;
        }

        public IWorkspaceComponent2 CreateControl(bool inDesignMode, IRecordContext context)
        {
          
            addinsSettings.Instance.defaultTemplDir = this.DefaultTemplateDirectory;
            addinsSettings.Instance.templateCustomField = this.templateCustomField;


            return new MailMergeWorkspaceComponent(inDesignMode,  _context, context);
        }

        #endregion

        #region IFactoryBase Members

        public System.Drawing.Image Image16
        {
            get;
            set;
        }

        public string Text
        {
            get;
            set;
        }

        public string Tooltip
        {
            get;
            set;
        }

        #endregion

        #region IAddInBase Members

        private IGlobalContext _context;
        public bool Initialize(IGlobalContext context)
        {
            _context = context;
            return true;
        }

        #endregion

        public MailMergeWorkspaceComponentFactory()
        {
            Text = "Mail Merge Control";
            Tooltip = "Mail Merge Control";
            Image16 = Properties.Resources.MailMerge64;
        }
    }

    public class MailMergeWorkspaceComponent : Panel, IWorkspaceComponent2
    {
        #region IWorkspaceComponent2 Members

        private IRecordContext _recContext;
        private IGlobalContext _gContext;
        private workspaceTypes workspaceEnumType = workspaceTypes.Invalid;
        /*
         * we have to do this strangness (enumerating types) since custom objects are 
         * represented as igeneric, so we can't just iterate over the workspace object
         * and look at the type.
         */
        enum workspaceTypes { Contact, Incident, Pledge, Invalid };
        

        public MailMergeWorkspaceComponent(bool inDesignMode, IGlobalContext gContext, IRecordContext rContext)
        {
            this._recContext = rContext;
            this._gContext = gContext;
               
            Button btnMailMerge = new Button();
            btnMailMerge.Text = "Mail Merge";
            
            if (inDesignMode == false)
            {
                this._recContext.DataLoaded += new EventHandler(setupWorkspace);
                btnMailMerge.Click += new EventHandler(btnMailMerge_Click);
            }

            this.Controls.Add(btnMailMerge);
        }

        private void setupWorkspace(object sender, EventArgs e)
        {
            if (this.getWorkspaceType(this._recContext) == workspaceTypes.Invalid)
            {
                MessageBox.Show("This mail merge addin is only valid on Incident, Contact, and Pledge workspaces.");
            }
        }

        /**
         * figures out and returns if the addin is on a valid workspace.
         */
        private workspaceTypes getWorkspaceType(IRecordContext record)
        {
            //IGenericObject pledge = this._context.GetWorkspaceRecord("donation$pledge") as IGenericObject;
            //IIncident incident = this._context.GetWorkspaceRecord(WorkspaceRecordType.Incident) as IIncident;
            //IContact contact = this._context.GetWorkspaceRecord(WorkspaceRecordType.Contact) as IContact;
            switch (record.WorkspaceType)
            {
                case WorkspaceRecordType.Contact:
                    this.workspaceEnumType = workspaceTypes.Contact;
                    break;
                case WorkspaceRecordType.Incident:
                    this.workspaceEnumType = workspaceTypes.Incident;
                    break;
                case WorkspaceRecordType.Generic:
                    IGenericObject pledge = this._recContext.GetWorkspaceRecord("donation$pledge") as IGenericObject;
                    if (pledge != null)
                    {
                        this.workspaceEnumType = workspaceTypes.Pledge;
                    }
                    break;
                default:
                    this.workspaceEnumType = workspaceTypes.Invalid;
                    break;
            }
            
            return this.workspaceEnumType;
        }

        void btnMailMerge_Click(object sender, EventArgs e)
        {
            
            Cursor currentCursor = Cursor.Current;
            Cursor.Current = Cursors.WaitCursor;
            
            try
            {
                SortedDictionary<string, string> recordData = new SortedDictionary<string, string>();
                MailMergeModalDialog mailMergeModelDialog;
                switch (this.workspaceEnumType)
                {
                    case workspaceTypes.Contact:
                        IContact contact = this._recContext.GetWorkspaceRecord(WorkspaceRecordType.Contact) as IContact;
                        mailMergeModelDialog = new MailMergeModalDialog(getContactData(contact.ID), this._gContext, addinsSettings.Instance.defaultTemplDir, addinsSettings.Instance.templateCustomField);
                        mailMergeModelDialog.ShowDialog();
                        break;
                    case workspaceTypes.Incident:
                        IIncident incident = this._recContext.GetWorkspaceRecord(WorkspaceRecordType.Incident) as IIncident;
                        IContact incContact = this._recContext.GetWorkspaceRecord("Contact") as IContact;
                        mailMergeModelDialog = new MailMergeModalDialog(getIncidentData(incident.ID, incContact.ID), this._gContext, addinsSettings.Instance.defaultTemplDir, addinsSettings.Instance.templateCustomField);
                        mailMergeModelDialog.ShowDialog();
                        break;
                    case workspaceTypes.Pledge:
                        IGenericObject pledge = this._recContext.GetWorkspaceRecord("donation$pledge") as IGenericObject;
                        IContact pledgeContact = this._recContext.GetWorkspaceRecord(WorkspaceRecordType.Contact) as IContact;
                        long primaryCid = -1;
                        if (pledgeContact != null)
                        {
                            primaryCid = pledgeContact.ID;
                        }
                        mailMergeModelDialog = new MailMergeModalDialog(getPledgeData(pledge, primaryCid), this._gContext, addinsSettings.Instance.defaultTemplDir, addinsSettings.Instance.templateCustomField);
                        mailMergeModelDialog.ShowDialog();
                        break;
                    case workspaceTypes.Invalid:
                        return;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("An unexpected problem occured.  The server said: " + ex.Message);
            }
            finally
            {
                this.Cursor = currentCursor;
            }

        }

        private SortedDictionary<string,string> getContactData(int c_id)
        {
            //get our data and create the dialog
            SortedDictionary<string, string> recordData = new SortedDictionary<string, string>();
            try
            {
                cwsModel cws = cwsModel.getInstance();
                cws.InitializeConnectWebService(this._gContext);
                recordData = cws.getContactData(c_id);
            }
            catch (Exception ex)
            {
                MessageBox.Show("There was an error retrieving field data.  All data may not be available. The server said: " + ex.Message );
                return recordData;
            }
            return recordData;
        }

        private SortedDictionary<string, string> getIncidentData(int i_id, int c_id)
        {
            //get our data and create the dialog
            SortedDictionary<string, string> recordData = new SortedDictionary<string, string>();
            try
            {
                cwsModel cws = cwsModel.getInstance();
                cws.InitializeConnectWebService(this._gContext);
                recordData = cws.getIncidentData(i_id, c_id);
            }
            catch (Exception ex)
            {
                MessageBox.Show("There was an error retrieving field data.  All data may not be available. The server said: " + ex.Message);
                return recordData;
            }
            return recordData;
        }

        private SortedDictionary<string, string> getPledgeData(IGenericObject pledge,long c_id)
        {
            //get our data and create the dialog
            SortedDictionary<string, string> recordData = new SortedDictionary<string, string>();
            try
            {
                cwsModel cws = cwsModel.getInstance();
                cws.InitializeConnectWebService(this._gContext);
                IGenericField child = pledge.GenericFields.Where(field => field.Name.Equals("Child")).First() as IGenericField;
                if (child.DataValue.Value != null)
                {
                    recordData = cws.getPledgeData(pledge.Id, long.Parse(child.DataValue.Value.ToString()), c_id);
                }
                else
                {
                    recordData = cws.getPledgeData(pledge.Id, c_id);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("There was an error retrieving field data.  All data may not be available. The server said: " + ex.Message + ex.InnerException + ex.StackTrace);
                return recordData;
            }
            return recordData;
        }



        public bool ReadOnly
        {
            get;
            set;
        }

        public void RuleActionInvoked(string actionName)
        {
        }

        public string RuleConditionInvoked(string conditionName)
        {
            return "";
        }

        #endregion

        #region IAddInControl Members

        public Control GetControl()
        {
            return this;
        }

        #endregion
    }


    [AddIn("Mail Merge Ribbon Tab", Version = "1.0.0.0")]
    public class MailMergeRibbonTab : IGlobalRibbonTab
    {
        public MailMergeRibbonTab()
        {
            KeyTips = "mm";
            Order = 1;
            Text = "Mail Merge";
            Visible = true;
        }

        #region IGlobalRibbonTab Members

        public string KeyTips { get; private set; }

        public int Order { get; private set; }

        public string Text { get; private set; }

        public bool Visible { get; private set; }

        public event EventHandler VisibleChanged;
        internal virtual void onVisibleChanged(PropertyChangedEventArgs e)
        {
            if (VisibleChanged != null)
                VisibleChanged(this, e);
        }

        #endregion

        #region IAddInBase Members

        public bool Initialize(IGlobalContext context)
        {
            return true;
        }

        #endregion
    }
    //TOD0 add ribbon button, ribbon payne, ribbon bar addin, onlyu get global contex with ribbon bar
    //addins dont exist in a sand box context, can blow up the app. be 
    //Single settings uses custom objects and settings file.  Single
    // REQUREMENT: create ribbion bar that will select a report and allow you when clicked will create a multiple contact mail merge control 
    //Implement MailMergeRibbonTab and MailMergeRibbonGroup read ribbion addin docmentation, Mauil Merge Ribbion tab, and MAil merge ribbion button, tab contains a group, contains a button
    // buttojn opens up new mail merge contact or content payne, which is a seperate addin button. 
    //ConnectWebServices for soap replaces RNConnect 
    //    mplement MailMergeContentPane, MailMergeRibbonTab, MailMergeRibbonButton and MailMergeRibbonGroup(edited)
    //Chekc the csw code for attaching documents.
    //Mail Merge for each For Run Mail Merge. Get all report data, create a seperate document for each row in report. then attach to contact record, save to output directory. 

    //[12:06] 
    //(code commented out)

    //[12:06]
    //    Read ribbon bar addin docs

    //    ben[12:13 PM]
    //change analytics report field to accept integer report id

    //[12:13]
    //Add button next to report id to "pull report"
    [AddIn("Mail Merge Ribbon Group", Version = "1.0.0.0")]
    public class MailMergeRibbonGroup : IGlobalRibbonGroup
    {
        public MailMergeRibbonGroup()
        {
            Order = 1;
            TabName = "MailMergeAddIn.MailMergeRibbonTab";
            Text = "Mail Merge";
            Visible = true;
        }

        #region IGlobalRibbonGroup Members

        public int Order { get; private set; }

        public string TabName { get; private set; }

        public string Text { get; private set; }

        public bool Visible { get; private set; }

        public event EventHandler VisibleChanged;
        internal virtual void onVisibleChanged(PropertyChangedEventArgs e)
        {
            if (VisibleChanged != null)
                VisibleChanged(this, e);
        }

        #endregion

        #region IAddInBase Members

        public bool Initialize(IGlobalContext context)
        {
            cwsModel cws = cwsModel.getInstance();
            cws.InitializeConnectWebService(context);
            return true;
        }

        #endregion
    }

    [AddIn("Mail Merge Ribbon Button", Version = "1.0.0.0")]
    public class MailMergeRibbonButton : IGlobalRibbonButton
    {
        public MailMergeRibbonButton()
        {
            Enabled = true;
            GroupName = "Mail Merge";
            Visible = true;
            Enabled = true;
            Text = "Merge";
            Tooltip = "Perform a Mail Merge";
            Image32 = Properties.Resources.MailMerge64;
            GroupName = "MailMergeAddIn.MailMergeRibbonGroup";
        }

        #region IGlobalRibbonButton Members

        public void Click()
        {
            if (!AutoClient.automationContext.FindAndFocus(MailMergeContentPane.UniqueId))
            {
        
                AutoClient.automationContext.OpenEditor(new MailMergeContentPane());
            }
        }

        public bool Enabled { get; private set; }

        public event EventHandler EnabledChanged;
        internal virtual void onEnabledChanged(PropertyChangedEventArgs e)
        {
            if (EnabledChanged != null)
                EnabledChanged(this, e);
        }

        public string GroupName { get; private set; }

        public System.Drawing.Image Image16 { get; private set; }

        public System.Drawing.Image Image32 { get; private set; }

        public string KeyTips { get; private set; }

        public int Order { get; private set; }

        public System.Windows.Forms.Keys Shortcut { get; private set; }

        public string Text { get; private set; }

        public string Tooltip { get; private set; }

        public bool Visible { get; private set; }

        public event EventHandler VisibleChanged;
        internal virtual void onVisibleChanged(PropertyChangedEventArgs e)
        {
            if (VisibleChanged != null)
                VisibleChanged(this, e);
        }

        #endregion

        #region IAddInBase Members

        public bool Initialize(IGlobalContext context)
        {
            return true;
        }

        #endregion
    }

    [AddIn("Mail Merge Content Pane", Version = "1.0.0.0")]
    public class MailMergeContentPane : Panel, IContentPaneControl
    {
        private MailMergeControl mailMergeControl;

        public MailMergeContentPane()
        {
            UniqueId = UniqueID = "MailMergeContentPane";
            Text = "Mail Merge";

            mailMergeControl = new MailMergeControl();
            this.Controls.Add(mailMergeControl);
            this.Dock = DockStyle.Fill;
            this.Image16 = Properties.Resources.MailMerge16;
        }

        #region IContentPaneControl Members

        public bool BeforeClose()
        {
            // check to see if a mail merge is being performed here// if it is, the tab cannot be closed
            if (mailMergeControl.MergeInProgress)
            {
                MessageBox.Show("A merge is currently in progress. Please wait for the merge to complete or cancel it before continuing.", "Merge in progress");
                return false;
            }

            return true;
        }

        public void Closed()
        {
        }

        public System.Drawing.Image Image16 { get; private set; }

        public IList<IEditorRibbonButton> RibbonButtons { get; private set; }

        // public string Text { get; private set; }

        public string UniqueID { get; private set; }

        public static string UniqueId { get; private set; }

        #endregion

        #region IAddInControl Members

        public System.Windows.Forms.Control GetControl()
        {
            return this;
        }

        #endregion

        

       

    }

}
