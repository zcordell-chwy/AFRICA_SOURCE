using System;
using System.Collections.Generic;
using MailMergeAddIn.RightNowService;

//using obj.api.rightnow.com;
//using api.rightnow.com;
//using fault.api.rightnow.com;

namespace MailMergeAddIn
{
    /// <summary>
    /// TODO: Determine if this class needs to be modified to use the current soap services to replace the old RightNowConnct outdated assembly? 
    ///  http://africanewlife--tst.custhelp.com/cgi-bin/africanewlife.cfg/services/soap?xsd=base 
    /// </summary>
    [Obsolete("This class is out dated and has been replaced by the cws.Model class.")]
    public class RnConnect
    {
        private static RnConnect instance;
        //private RNOWObjectFactory of;
        private RightNowSyncPortClient rightNowSyncPortClient { get; set; }
        private string username;

        public bool isConnected { get; private set; }

        public static RnConnect getInstance()
        {
            return instance ?? (instance = new RnConnect());
        }

        /// <summary>
        /// Prevents a default instance of the <see cref="RnConnect"/> class from being created.
        /// opens RightNowSyncPort Service and sets connected property to true.
        /// </summary>
        private RnConnect()
        {
            try
            {
                //of = new RNOWObjectFactory(string.Format("{0}xml_api/soap_api.php", AutoClient.globalContext.InterfaceURL));

                rightNowSyncPortClient =  new RightNowSyncPortClient();
                rightNowSyncPortClient.Open();
                isConnected = true;
                //if (of.relogin(AutoClient.globalContext.InterfaceName, AutoClient.globalContext.Login))
                //{
                //    isConnected = true;
                //}
            }
            catch (Exception ex)
            {
                System.Windows.Forms.MessageBox.Show(ex.Message.ToString(), "Error" + ex.Message + ex.InnerException);
            }
        }

        /// <summary>
        /// Gets the report data.
        /// </summary>
        /// <param name="acId">The ac identifier.</param>
        /// <returns></returns>
        //public object[][] getReportData(int acId)
       public AnalyticsReport getReportData(int acId) //TODO: Determine if we want to return the RN Object here or the report object its self
        {
            //if (!isConnected)
            //{
            //    return new object[0][];
            //}
            var analytictsReport = new AnalyticsReport();
            if (!isConnected)
            {
                return analytictsReport;
            }
            //AnalyticsReport

            //try
            //{
            //   // RNOWAcFilter[] filters = new RNOWAcFilter[0]; // we aren't supporting run-time filters, so don't add any
            //    //of.Get()
            //    return of.ExecuteReport(acId, filters);
            //}
            //catch (RNOWException ex)
            //{
            //    System.Windows.Forms.MessageBox.Show(ex.Message.ToString(), "Report error");
            //}

            //AnalyticsReport analyticsReport = new AnalyticsReport();
            //Specify a report ID of Public Reports>Common>Data integration>Opportunities
            ID reportID = new ID();
           // reportID.id = 13029;
            reportID.id = acId;
            reportID.idSpecified = true;
            analytictsReport.ID = reportID;
            ClientInfoHeader clientInfoHeader = new ClientInfoHeader();
            clientInfoHeader.AppID = "Get an Analytics Report"; //TODO create a constant for this value here, or modify method signature to accept App ID as a parameter 
            //Create a filter and specify the filter name
            //Assigning the filter created in desktop agent to the new analyticts filter 
            AnalyticsReportFilter filter = new AnalyticsReportFilter();

            //Apply the filter to the AnalyticsReport object
            analytictsReport.Filters = (new AnalyticsReportFilter[] { filter });
            GetProcessingOptions processingOptions = new GetProcessingOptions();
            processingOptions.FetchAllNames = true;

            RNObject[] getAnalyticsObjects = new RNObject[] { analytictsReport };
            try
            {
                rightNowSyncPortClient.Open();
                RNObject[] rnObjects = rightNowSyncPortClient.Get(clientInfoHeader, getAnalyticsObjects, processingOptions);
                rightNowSyncPortClient.Close();
                analytictsReport = (AnalyticsReport)rnObjects[0];
            }
            catch (Exception ex)
            {
                System.Windows.Forms.MessageBox.Show(ex.Message.ToString(), "Error" + ex.Message + ex.InnerException); //TODO: Determine if more descriptive and location oriented error messages are desired here.
            }
        
            //return new object[0][];

            return analytictsReport;
        }

        public int attachFileToContact(int cId, string fileName, string userFileName)
        {
            // create attachment
            //RNOWFAttachNest fattach = new RNOWFAttachNest();
            //fattach.Action = 1;
            //if (fileName.EndsWith(".docx"))
            //{
            //    fattach.ContentType = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
            //}
            //else
            //{
            //    fattach.ContentType = "application/pdf";
            //}
            
            //fattach.UploadFileName = fileName;
            //fattach.UserFName = userFileName;
            //fattach.Descr = "Created by Mail Merge Add-In";

            //List<RNOWFAttachNest> attachments = new List<RNOWFAttachNest>();
            //attachments.Add(fattach);

            //// attach it to the contact
            //RNOWContact contact = new RNOWContact(cId);
            //contact.FAttach = attachments;

            //// update the contact
            //int rv = of.update(contact);
            //return rv;
            return 0;
        }
    }
}
