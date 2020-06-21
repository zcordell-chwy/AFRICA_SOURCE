using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using RightNow.AddIns.AddInViews;
using System.Windows.Forms;
using System.Drawing;
using System.AddIn;


namespace ccProcessing
{
    public class ccProcessingAddin : IWorkspaceComponent2
    {
        bool inDesign;
        IGlobalContext contextObj;
        ccProcessingControl addinControl;
        cwsModel model;

        public ccProcessingAddin(bool inDesignMode, IGlobalContext context)
        {
            this.inDesign = inDesignMode;
            this.contextObj = context;
            this.model = new cwsModel(context);
            if (!inDesignMode)
            {
                context.AutomationContext.CurrentWorkspace.DataLoaded += CurrentWorkspace_DataLoaded;
                context.AutomationContext.CurrentWorkspace.Closing += CurrentWorkspace_Closing;
            }
        }

        void CurrentWorkspace_Closing(object sender, System.ComponentModel.CancelEventArgs e)
        {
            IContact workspaceContact = this.contextObj.AutomationContext.CurrentWorkspace.GetWorkspaceRecord(RightNow.AddIns.Common.WorkspaceRecordType.Contact) as IContact;
            if (workspaceContact == null)
            {
                MessageBox.Show("There is no contact associated with this donation, perhaps you need to save the donation?");
                return;
            }


           bool isSuccessAddTrans =  this.addinControl.closingAddTransaction();
           if (! isSuccessAddTrans)
           {
               e.Cancel = true;
           }
        }

        void CurrentWorkspace_DataLoaded(object sender, EventArgs e)
        {

            if (!inDesign)
            {
                IContact workspaceContact = this.contextObj.AutomationContext.CurrentWorkspace.GetWorkspaceRecord(RightNow.AddIns.Common.WorkspaceRecordType.Contact) as IContact;
                if (workspaceContact == null)
                {
                    MessageBox.Show("There is no contact associated with this donation, perhaps you need to save the donation?");
                    return;
                }

                if (this.addinControl == null)
                {
                    this.GetControl();
                }

                IGenericObject wsDonation = this.contextObj.AutomationContext.CurrentWorkspace.GetWorkspaceRecord("donation$Donation") as IGenericObject;
                if (wsDonation == null)
                {
                    MessageBox.Show("The CC Processing Addin is only available for Donation Workspaces");
                    return;
                }
                if (wsDonation.Id == null || wsDonation.Id < 1)
                {
                   // MessageBox.Show("Please save the donation first.");
                    return;
                }

                this.model = new cwsModel(this.contextObj);
                this.addinControl.setupControl(this.model, workspaceContact, wsDonation);
            }
        }


        public bool ReadOnly
        {
            get
            {
                return false;
            }
            set { }
        }

        public void RuleActionInvoked(string actionName)
        {
            if (actionName == ccProcessing.serverSettings.Instance.actionName_addPaymentMethod)
            {
                this.displayAddPaymentMethod();
            }
            else if (actionName == ccProcessing.serverSettings.Instance.actionName_initiateRefund)
            {
                this.initiateRefund();

            }
            else if (actionName == ccProcessing.serverSettings.Instance.actionName_makePayment)
            {
                this.makePayment();
            }

        }

        public void displayAddPaymentMethod()
        {
        }

        public void initiateRefund()
        {
            ccProcessingControl control = this.GetControl() as ccProcessingControl;
            if (control == null)
            {
                return;
            }

            control.displayMakeRefund();
        }
        public void makePayment()
        {

            ccProcessingControl control = this.GetControl() as ccProcessingControl;
            if (control == null)
            {
                return;
            }

            control.displayMakePayment();
        }

        public string RuleConditionInvoked(string conditionName)
        {
            return "";
        }

        public System.Windows.Forms.Control GetControl()
        {
            if (this.addinControl == null){
              this.addinControl = new ccProcessingControl();
            }
            return this.addinControl;
        }
    }



    [AddIn("CC Processing Addin", Version = "1.0")]
    public class ccProcessingAddinFactory : IWorkspaceComponentFactory2
    {
        IGlobalContext context;
        public IWorkspaceComponent2 CreateControl(bool inDesignMode, IRecordContext context)
        {
            return new ccProcessingAddin(inDesignMode, this.context);
        }

        public System.Drawing.Image Image16
        {
            get { return ccProcessing.Properties.Resources.icon; }
        }

        public string Text
        {
            get { return "Credit Card Processing"; }
        }

        public string Tooltip
        {
            get { return "Credit Card Processing Addin"; }
        }

        public bool Initialize(IGlobalContext context)
        {
            this.context = context;
            return true;
        }

        [ServerConfigProperty(DefaultValue = "initiateRefund")]
        public string actionName_initiateRefund
        {
            get { return ccProcessing.serverSettings.Instance.actionName_initiateRefund; }
            set { ccProcessing.serverSettings.Instance.actionName_initiateRefund = value; }
        }

        [ServerConfigProperty(DefaultValue = "makePayment")]
        public string actionName_makePayment
        {
            get { return ccProcessing.serverSettings.Instance.actionName_makePayment; }
            set { ccProcessing.serverSettings.Instance.actionName_makePayment = value; }
        }

        [ServerConfigProperty(DefaultValue = "addPaymentMethod")]
        public string actionName_addPaymentMethod
        {
            get { return ccProcessing.serverSettings.Instance.actionName_addPaymentMethod; }
            set { ccProcessing.serverSettings.Instance.actionName_addPaymentMethod = value; }
        }

        
       
        // [ServerConfigProperty(DefaultValue = "https://africanewlife--tst.custhelp.com/app/payment/testPost")]
        [ServerConfigProperty(DefaultValue = "https://partnerportal.fasttransact.net/Web/Payment.aspx")]
        public string frontstreamUrl_makePayment
        {
            get { return ccProcessing.serverSettings.Instance.frontstreamHosted_makePayment; }
            set { ccProcessing.serverSettings.Instance.frontstreamHosted_makePayment = value; }
        }

       // [ServerConfigProperty(DefaultValue = "PdgAaExjPzkhF1aD1W43Zbvc48/Rl/UEkVDYS35vHRsQyFXVFnk2jlzD1x14y5Vp")]
        [ServerConfigProperty(DefaultValue = "yAC/GV7JObMmlaoQGH7toRJ54N+7hWeddczX8nGokssyrfaaoeYRbtcDgr0oYMbs")]
        public string frontstream_userToken
        {
            get { return ccProcessing.serverSettings.Instance.frontstreamHosted_userToken; }
            set { ccProcessing.serverSettings.Instance.frontstreamHosted_userToken = value; }
        }

        //[ServerConfigProperty(DefaultValue = "FThost5193")]
        [ServerConfigProperty(DefaultValue = "xtti3002")]
        public string frontstreamHosted_username
        {
            get { return ccProcessing.serverSettings.Instance.frontstreamHosted_username; }
            set { ccProcessing.serverSettings.Instance.frontstreamHosted_username = value; }
        }

        //[ServerConfigProperty(DefaultValue = "1245")]
        [ServerConfigProperty(DefaultValue = "1436")]
        public string frontstream_merchantKey
        {
            get { return ccProcessing.serverSettings.Instance.frontstreamHosted_merchantKey; }
            set { ccProcessing.serverSettings.Instance.frontstreamHosted_merchantKey = value; }
        }


      [ServerConfigProperty(DefaultValue = "2B2A1C9F7ED4E76600184CA9D")]
              public string frontstreamHosted_merchantToken
        {
            get { return ccProcessing.serverSettings.Instance.frontstreamHosted_merchantToken; }
            set { ccProcessing.serverSettings.Instance.frontstreamHosted_merchantToken = value; }
        }


        //[ServerConfigProperty(DefaultValue = "https://africanewlife--tst.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/frontstreampostback.php")]
        [ServerConfigProperty(DefaultValue = "https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/frontstreampostback.php")]
        public string frontstreamHosted_postbackUrl
        {
            get { return ccProcessing.serverSettings.Instance.frontstreamHosted_postbackUrl; }
            set { ccProcessing.serverSettings.Instance.frontstreamHosted_postbackUrl = value; }
        }


        //[ServerConfigProperty(DefaultValue = "https://africanewlife--tst.custhelp.com/euf/assets/themes/africa/images/anlm-header-logo.png")]
        [ServerConfigProperty(DefaultValue = "https://africanewlife.custhelp.com/euf/assets/themes/africa/images/anlm-header-logo.png")]
        public string frontstreamHosted_headerUrl
        {
            get { return ccProcessing.serverSettings.Instance.frontstreamHosted_headerUrl; }
            set { ccProcessing.serverSettings.Instance.frontstreamHosted_headerUrl = value; }

        }
         
        //[ServerConfigProperty(DefaultValue = "iihj3840")]
        [ServerConfigProperty(DefaultValue = "xtti3002")]
        public string frontstream_apiUsername
        {
            get { return ccProcessing.serverSettings.Instance.frontstream_apiUsername; }
            set { ccProcessing.serverSettings.Instance.frontstream_apiUsername = value; }
        }

        //[ServerConfigProperty(DefaultValue = "6919RUw7")]
        [ServerConfigProperty(DefaultValue = "VI3VPAm0")]
        public string frontstream_apiPassword
        {
            get { return ccProcessing.serverSettings.Instance.frontstream_apiPassword; }
            set { ccProcessing.serverSettings.Instance.frontstream_apiPassword = value; }
        }

        [ServerConfigProperty(DefaultValue = "https://secure.ftipgw.com/smartpayments/transact.asmx")]
        public string frontstream_apiEndpoint
        {
            get { return ccProcessing.serverSettings.Instance.frontstream_apiEndpoint; }
            set { ccProcessing.serverSettings.Instance.frontstream_apiEndpoint = value; }
        }
        
    }
}
