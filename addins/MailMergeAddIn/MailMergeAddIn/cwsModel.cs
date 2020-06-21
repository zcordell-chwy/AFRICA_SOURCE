using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.IO;

using System.ServiceModel;
using System.ServiceModel.Channels;
using RightNow.AddIns.AddInViews;
using RightNow.AddIns.Common;
using MailMergeAddIn.RightNowService;
using com.rightnow.MailMerge.WebService;

namespace MailMergeAddIn
{
    class cwsModel
    {

        /**********************************************Class Variables**********************************************/
        private String connectUser = "";
        private String connectPass = "";
        private static cwsModel instance;
        private IGlobalContext globalContext;
        //private RightNowSyncPortClient rnService;
        private RnConnSvc.RightNowSyncPortClient rnService { get;set; }
        private RnConnSvc.ClientInfoHeader rnClientInfoHeader;
        enum workspaceTypes { Contact, Incident, Pledge, Invalid };
        /**
         * Indicates if the username, password, etc have been initialized properly.
         */
        private bool initialized = false;
        private string uninitializedErrorMsg = "The data model has not been initialized.";

        private IWorkspaceRecord _currentRecord;
        public IWorkspaceRecord CurrentRecord
        {
            get
            {

                switch (this.CurrentWorkspaceType)
                {
                    case workspaceTypes.Contact:
                        this._currentRecord = this.globalContext.AutomationContext.CurrentWorkspace.GetWorkspaceRecord("Contact");
                        break;
                    case workspaceTypes.Incident:
                        this._currentRecord = this.globalContext.AutomationContext.CurrentWorkspace.GetWorkspaceRecord("Incident");
                        break;
                    case workspaceTypes.Pledge:
                        this._currentRecord = this.globalContext.AutomationContext.CurrentWorkspace.GetWorkspaceRecord("donation$pledge") as IWorkspaceRecord;
                        break;
                }

                return this._currentRecord;
            }
        }

        private workspaceTypes _currentWorkspacetype = workspaceTypes.Invalid;
        private workspaceTypes CurrentWorkspaceType
        {
            get
            {
                switch (this.globalContext.AutomationContext.CurrentWorkspace.WorkspaceType)
                {
                    case WorkspaceRecordType.Contact:
                        this._currentWorkspacetype = workspaceTypes.Contact;
                        break;
                    case WorkspaceRecordType.Incident:
                        this._currentWorkspacetype = workspaceTypes.Incident;
                        break;
                    case WorkspaceRecordType.Generic:
                        IGenericObject pledge = this.globalContext.AutomationContext.CurrentWorkspace.GetWorkspaceRecord("donation$pledge") as IGenericObject;
                        if (pledge != null)
                        {
                            this._currentWorkspacetype = workspaceTypes.Pledge;
                        }
                        break;
                    default:
                        this._currentWorkspacetype = workspaceTypes.Invalid;
                        break;
                }

                return this._currentWorkspacetype;
            }
        }

        /**
        * Constructor
        */
        public cwsModel() //TODO: Determine why this was private, why??
        {

        }

    

        /**********************************************Public Functions**********************************************/

        /**
         * Singleton accessor
         */
        public static cwsModel getInstance()
        {
            return instance ?? (instance = new cwsModel());
        }

        /**
        * sets up initial class data, permissions, etc.
         * 
         * Required to be run prior to any web service calls
        */
        public void InitializeConnectWebService(IGlobalContext gContext)
        {
            this.globalContext = gContext;
            EndpointAddress endPointAddr = new EndpointAddress(gContext.GetInterfaceServiceUrl(ConnectServiceType.Soap));
            BasicHttpBinding binding = new BasicHttpBinding(BasicHttpSecurityMode.TransportWithMessageCredential);
            binding.Security.Message.ClientCredentialType = BasicHttpMessageCredentialType.UserName;
           
            // Optional depending upon use cases
            binding.MaxReceivedMessageSize = 1024 * 1024;
            binding.MaxBufferSize = 1024 * 1024;
            binding.MessageEncoding = WSMessageEncoding.Mtom;


            RnConnSvc.RightNowSyncPortClient client = new RnConnSvc.RightNowSyncPortClient(binding, endPointAddr);

            BindingElementCollection elements = client.Endpoint.Binding.CreateBindingElements();
            elements.Find<SecurityBindingElement>().IncludeTimestamp = false;
            client.Endpoint.Binding = new CustomBinding(elements);

            this.rnClientInfoHeader = new RnConnSvc.ClientInfoHeader {AppID = "mailMergeAddin"};

            gContext.PrepareConnectSession(client.ChannelFactory);

            this.rnService = client;

            this.initialized = true;
        }

        /**
         * Returns all available data for the passed contact record
         */
        public SortedDictionary<string, string> getContactData(int c_id)
        {
            if (!this.initialized)
            {
                throw new InvalidOperationException(this.uninitializedErrorMsg);
            }

            if (c_id == null || c_id < 1)
            {
                throw new Exception("Invalid contact id");
            }
            //use the generic interface so that we can enumerate over all of the available fields without having to list them out
            RnConnSvc.GenericObject genericContact = getGenericContact(c_id);

            RnConnSvc.RNObject[] rn_objects = new RnConnSvc.RNObject[] { genericContact };
            RnConnSvc.GetProcessingOptions options = new RnConnSvc.GetProcessingOptions();
            options.FetchAllNames = true;

            RnConnSvc.RNObject[] rnObjects = this.rnService.Get(this.rnClientInfoHeader, rn_objects, options);
            genericContact = rnObjects[0] as RnConnSvc.GenericObject;


            SortedDictionary<string, string> contactDict = this.parseGenericObject(genericContact, "Contact: ");
            return contactDict;
        }

        /**
         * Returns all available data for the passed pledge.  Also returns data from the associated contact, and child records.
         */
        public SortedDictionary<string, string> getPledgeData(long pledge_id, long child_id, long c_id)
        {
            if (!this.initialized)
            {
                throw new InvalidOperationException(this.uninitializedErrorMsg);
            }
            if (pledge_id == null || pledge_id < 1 || c_id == null || c_id < 1)
            {
                throw new Exception("Invalid contact, or pledge id");
            }

            RnConnSvc.GenericObject pledgeTemplate = getGenericObjectTemplate("donation", "pledge", pledge_id);
            RnConnSvc.GenericObject genericContact = getGenericContact(c_id);
            RnConnSvc.GenericObject childTemplate = null;
            if (child_id > 0)
            {
                childTemplate = getGenericObjectTemplate("sponsorship", "Child", child_id);
            }

            RnConnSvc.GetProcessingOptions gpo = new RnConnSvc.GetProcessingOptions();
            gpo.FetchAllNames = true;
            RnConnSvc.RNObject[] permissionObjectTemplates;
            if (child_id > 0)
            {
                 permissionObjectTemplates = permissionObjectTemplates = new RnConnSvc.RNObject[] { pledgeTemplate, genericContact, childTemplate };
            }
            else
            {
                permissionObjectTemplates = permissionObjectTemplates = new RnConnSvc.RNObject[] { pledgeTemplate, genericContact };
            }

            //let any exceptions bubble up to the caller
            RnConnSvc.RNObject[] invData = this.rnService.Get(this.rnClientInfoHeader, permissionObjectTemplates, gpo);

            pledgeTemplate = invData[0] as RnConnSvc.GenericObject;
            SortedDictionary<string, string> pledgeDict = this.parseGenericObject(pledgeTemplate, "Pledge: ");
            genericContact = invData[1] as RnConnSvc.GenericObject;
            pledgeDict = this.parseGenericObject(genericContact, "Contact: ", pledgeDict);
            if (child_id > 0)
            {
                childTemplate = invData[2] as RnConnSvc.GenericObject;
                pledgeDict = this.parseGenericObject(childTemplate, "Child: ", pledgeDict);
            }

            return pledgeDict;
        }
        public SortedDictionary<string, string> getPledgeData(long pledge_id, long c_id)
        {
            return this.getPledgeData(pledge_id, -1, c_id);
        }

        /**
         * Returns all available data for the passed incident, as well as all available data for the associated contact.
         */
        public SortedDictionary<string, string> getIncidentData(int i_id, int c_id)
        {
            if (!this.initialized)
            {
                throw new InvalidOperationException(this.uninitializedErrorMsg);
            }
            if (i_id == null || i_id < 1 || c_id == null || c_id < 1)
            {
                throw new Exception("Invalid contact or incident id");
            }
            //use the generic interface so that we can enumerate over all of the available fields without having to list them out
            RnConnSvc.GenericObject genericIncident = getGenericIncident(i_id);
            RnConnSvc.GenericObject genericContact = getGenericContact(c_id);

            RnConnSvc.RNObject[] rn_objects = new RnConnSvc.RNObject[] { genericIncident, genericContact };
            RnConnSvc.GetProcessingOptions options = new RnConnSvc.GetProcessingOptions();
            options.FetchAllNames = true;

            rn_objects = this.rnService.Get(this.rnClientInfoHeader, rn_objects, options);

            genericIncident = rn_objects[0] as RnConnSvc.GenericObject;
            SortedDictionary<string, string> incidentDict = this.parseGenericObject(genericIncident, "Incident: ");

            genericContact = rn_objects[1] as RnConnSvc.GenericObject;
            incidentDict = this.parseGenericObject(genericContact, "Contact: ", incidentDict);

            return incidentDict;
        }


        /**
         * Examines the current workspace (as gathered from the global context) and attaches the passed file 
         * to that record.
         */
        public bool attachFileToRecordCurrentWorkspace(string fileLocation, string userFName, string mimeType)
        {
            if (!this.initialized)
            {
                throw new InvalidOperationException(this.uninitializedErrorMsg);
            }
            if (!File.Exists(fileLocation))
            {
                return false;
            }
            switch (this.CurrentWorkspaceType)
            {
                case workspaceTypes.Pledge:
                    IGenericObject pledge = this.CurrentRecord as IGenericObject;
                    this._addFileToObject(pledge.Id,"donation", "pledge", userFName, fileLocation, mimeType);
                    break;

                case workspaceTypes.Contact:
                    IContact contact = this.CurrentRecord as IContact;
                    this.addFileToContact(fileLocation, userFName, mimeType, contact.ID);
                    break;

                case workspaceTypes.Incident:
                    IIncident incident = this.CurrentRecord as IIncident;
                    this._addFileToIncident(incident.ID, userFName, fileLocation, mimeType);
                    break;
            }

            return true;
        }

        public void addFileToContact(string fileLocation, string userFName, string mimeType, int c_id)
        {
            if (!this.initialized)
            {
                throw new InvalidOperationException(this.uninitializedErrorMsg);
            }
            RnConnSvc.Contact newCon = new RnConnSvc.Contact();
            newCon.ID = new RnConnSvc.ID();
            newCon.ID.id = c_id;
            newCon.ID.idSpecified = true;
            RnConnSvc.FileAttachmentCommon[] fattach = new RnConnSvc.FileAttachmentCommon[1];
            fattach[0] = new RnConnSvc.FileAttachmentCommon();
            fattach[0].action = RnConnSvc.ActionEnum.add;
            fattach[0].actionSpecified = true;
            fattach[0].ContentType = mimeType;
            fattach[0].Data = this._ReadByteArrayFromFile(fileLocation);
            fattach[0].Description = "Mail Merge Attachment";
            fattach[0].FileName = userFName;
            fattach[0].Name = userFName;
            newCon.FileAttachments = fattach;

            RnConnSvc.UpdateProcessingOptions updateOpts = new RnConnSvc.UpdateProcessingOptions();
            updateOpts.SuppressExternalEvents = false;
            updateOpts.SuppressRules = false;

            //let exceptions bubble up
            RnConnSvc.RNObject[] updateObjects = new RnConnSvc.RNObject[] { newCon };
            this.rnService.Update(this.rnClientInfoHeader, updateObjects, updateOpts);
        }


        /// <summary>
        /// Returns the report definition
        /// </summary>
        /// <param name="anId"></param>
        /// <returns></returns>
        public RnConnSvc.AnalyticsReport getAnalyticsReportDefinition(int anId)
        {
            //Create new AnalyticsReport Object
            RnConnSvc.AnalyticsReport analyticsReport = new RnConnSvc.AnalyticsReport();
            RnConnSvc.AnalyticsReport report = new RnConnSvc.AnalyticsReport();
            //Specify a report ID of Public Reports>Common>Data integration>Opportunities
            RnConnSvc.ID reportID = new RnConnSvc.ID
            {
                id = anId,
                idSpecified = true
            };
            analyticsReport.ID = reportID;
            //Create a filter and specify the filter name
            //Assigning the filter created in desktop agent to the new analytics filter 
            RnConnSvc.AnalyticsReportFilter filter = new RnConnSvc.AnalyticsReportFilter();

            //Apply the filter to the AnalyticsReport object
            analyticsReport.Filters = (new RnConnSvc.AnalyticsReportFilter[] { filter });
            RnConnSvc.GetProcessingOptions processingOptions = new RnConnSvc.GetProcessingOptions { FetchAllNames = true };

            RnConnSvc.RNObject[] getAnalyticsObjects = new RnConnSvc.RNObject[] { analyticsReport };
            try
            {
                //RnClient.Open();
                RnConnSvc.RNObject[] rnObjects = this.rnService.Get(this.rnClientInfoHeader, getAnalyticsObjects, processingOptions);
                report = (RnConnSvc.AnalyticsReport)rnObjects[0];
            }
            catch (Exception ex)
            {
                throw;
            }
            finally
            {

            }

            return report;

        }


        /// <summary>
        /// Runs the analytics report. should be executed with a report value of 100541
        /// </summary>
        /// <param name="anId"></param>
        /// <param name="startIdx"></param>
        /// <param name="endIdx"></param>
        /// <returns>the personal donation data for those who donated to ANLM, ie, name address, city state, zip, postal code
        /// but, this method will dynamicall capture any report data that is returned. 
        /// </returns>
        /// 
        public List<ReportColWithRow> RunAnalyticsReport(int anId, int startIdx = 0, int endIdx = 5)
        {
            var reportRowList = new ReportColWithRow();
            var listOfRptRows = new List<ReportColWithRow>();

            RnConnSvc.AnalyticsReport analyticsReport = new RnConnSvc.AnalyticsReport();

            //Specify a report ID
            RnConnSvc.ID reportID = new RnConnSvc.ID();
            reportID.id = anId;
            analyticsReport.ID = reportID;
            analyticsReport.ID.idSpecified = true;

            RnConnSvc.CSVTableSet reportResult = new RnConnSvc.CSVTableSet();
            try
            {
                byte[] dataFromFile;
                reportResult = this.rnService.RunAnalyticsReport(this.rnClientInfoHeader, analyticsReport, endIdx-startIdx, startIdx, ",", false, true, out dataFromFile);
            }
            catch (Exception ex)
            {
                throw;
            }

            foreach (var returnedCsvTable in reportResult.CSVTables)
            {
                string[] headers = null;
                for (int resultRowIdx = 0; resultRowIdx <= returnedCsvTable.Rows.Length - 1; resultRowIdx++)
                {
                    reportRowList = new ReportColWithRow();
                    reportRowList.fields = new Dictionary<string, string>();
                    if (headers == null)
                    {
                        headers = returnedCsvTable.Columns.Split(',');
                    }

                    string[] rows = reportResult.CSVTables[0].Rows[resultRowIdx].Split(',');
                    for (int i = 0; i < headers.Length; i++)
                    {
                        reportRowList.fields.Add(headers[i], rows[i]);
                    }
                    listOfRptRows.Add(reportRowList);
                }

            }
            return listOfRptRows;
        }

        /// <summary>
        /// Gets the contact details for RPT. This report should be run after the RunAnalyticsReport with the report ID of 100539
        /// The donation data comes from report 100539.
        /// </summary>
        /// <param name="reportId">An identifier.</param>
        /// <param name="contacListId">The contact identifier.</param>
        /// <returns> a list of Donation information , in a tab delimited row format, with new lines terminating each row. </returns>
        public string GetContactDonationDataForRpt(int reportId, int contactId)
        {

            int reportColIdWithDate = 0;
            int reportColIdxTotal = 1;
            int reportColIdxLabel = 2;
            int reportColIdxChildName = 3;
            int rowLength = 143;

            decimal runningTotal = 0;
            var reportRowList = new ReportColWithRow();
            var listOfRptRows = new List<ReportColWithRow>();
            StringBuilder rowList = new StringBuilder();
            //Create new AnalyticsReport Object
            RnConnSvc.AnalyticsReport analyticsReport = new RnConnSvc.AnalyticsReport();
            //Specify a report ID of Public Reports>Common>Data integration>Opportunities
            RnConnSvc.ID reportID = new RnConnSvc.ID();

            reportID.id = reportId;
            reportID.idSpecified = true;

            analyticsReport.ID = reportID;

            //Assigning the filter as defined on the RightNow Analytics Report 
            RnConnSvc.AnalyticsReportFilter filter = new RnConnSvc.AnalyticsReportFilter();

            filter.Name = "Contact";
            filter.Values = new string[] { contactId.ToString() };

            List<RnConnSvc.AnalyticsReportFilter> filterList = new List<RnConnSvc.AnalyticsReportFilter>();
            filterList.Add(filter);
            //Apply the filter to the AnalyticsReport object
            analyticsReport.Filters = filterList.ToArray();
            RnConnSvc.CSVTableSet reportResult;
            byte[] dataFromFile;
            try
            {
                reportResult = this.rnService.RunAnalyticsReport(this.rnClientInfoHeader, analyticsReport, 1000, 0, "\t", false, true, out dataFromFile);
            }
            catch (Exception ex)
            {
                return rowList.ToString();
            }

            foreach (string reportRow in reportResult.CSVTables[0].Rows)
            {
                StringBuilder thisRow = new StringBuilder();
                string[] cols = reportRow.Split('\t');
                decimal donationAmt = Convert.ToDecimal(cols[reportColIdxTotal]);
                runningTotal += donationAmt;
                thisRow.Append(cols[reportColIdWithDate]);
                thisRow.Append("    ");
                string formattedDonationAmt = donationAmt.ToString("C2");
                thisRow.Append(formattedDonationAmt.PadRight(14));
                
                if (cols[reportColIdxLabel].Length > 0)
                {
                    thisRow.Append(cols[reportColIdxLabel]);
                }
                else if (cols[reportColIdxChildName].Length > 0)
                {
                    cols[reportColIdxChildName] = cols[reportColIdxChildName].Replace("\"", string.Empty);
                    string[] names = cols[reportColIdxChildName].Split(',').Select(name => name.Trim()).ToArray();
                    thisRow.Append(string.Join(", ", names.Distinct()));
                }

                string finalRow = thisRow.ToString();
                int paddingAmt = (finalRow.Length / rowLength) +1 * rowLength;
                rowList.Append(finalRow.PadRight(paddingAmt, ' '));
            }
            rowList.Append("Total Donations: " + runningTotal.ToString("C2"));

            return rowList.ToString();
        }

        /**
         * Removes any app settings that are currently stored for the referenced template file and
         * replaces those settings with the passed settings.
         */
        public bool storeAppSettings(Setting currentSettings)
        {
            if (!this.initialized)
            {
                throw new InvalidOperationException(this.uninitializedErrorMsg);
            }
            List<RnConnSvc.BatchRequestItem> requests = new List<RnConnSvc.BatchRequestItem>();

            //destroy old settings
            foreach (int settingId in this.getSettingObjIds(currentSettings.TmplFile))
            {
                requests.Add(createTemplateBatchDestroyObj(settingId));
            }

            //save our file settings
            requests = createTemplateBatchCreateObj(currentSettings, requests);
            RnConnSvc.BatchResponseItem[] batchResp = this.rnService.Batch(this.rnClientInfoHeader, requests.ToArray());

            return true;
        }

        /**
         * Returns any stored settings for the passed template file
         */
        public Setting getAppSettings(string templateFileName)
        {
            if (!this.initialized)
            {
                throw new InvalidOperationException(this.uninitializedErrorMsg);
            }
            //Setting appSettings = new Setting();

            string fileRoql = "select mail_merge.template_file from mail_merge.template_file where mail_merge.template_file.template_name = '" + templateFileName + "'";
            string fieldsRoql = "select mail_merge.field_map from mail_merge.field_map where mail_merge.field_map.template_file.template_name = '" + templateFileName + "'";
            RnConnSvc.QueryResultData[] fileResults;
            RnConnSvc.QueryResultData[] fieldResults;

            RnConnSvc.GenericObject roqlFileTemplate = new RnConnSvc.GenericObject();
            roqlFileTemplate.ObjectType = new RnConnSvc.RNObjectType();
            roqlFileTemplate.ObjectType.Namespace = "mail_merge";
            roqlFileTemplate.ObjectType.TypeName = "template_file";

            RnConnSvc.GenericObject roqlMergeFieldTemplate = new RnConnSvc.GenericObject();
            roqlMergeFieldTemplate.ObjectType = new RnConnSvc.RNObjectType();
            roqlMergeFieldTemplate.ObjectType.Namespace = "mail_merge";
            roqlMergeFieldTemplate.ObjectType.TypeName = "field_map";


            RnConnSvc.RNObject[] submitFileObjects = new RnConnSvc.RNObject[] { roqlFileTemplate };
            RnConnSvc.RNObject[] submitFieldObjects = new RnConnSvc.RNObject[] { roqlMergeFieldTemplate };
            //let exceptions propagate
            //connect chokes when a templates for two object from then same namespace are submitted.  Do 2 separate requests.
            fileResults = this.rnService.QueryObjects(this.rnClientInfoHeader, fileRoql, submitFileObjects, 1000);
            if (fileResults[0].Fault != null)
            {
                throw new Exception(fileResults[0].Fault.exceptionMessage);
            }
            fieldResults = this.rnService.QueryObjects(this.rnClientInfoHeader, fieldsRoql, submitFieldObjects, 1000);
            if (fieldResults[0].Fault != null)
            {
                throw new Exception(fieldResults[0].Fault.exceptionMessage);
            }
            Setting appSettings = new Setting();
            List<DataMapItem> dataMap = new List<DataMapItem>();
            if( fileResults[0].RNObjectsResult.Count() < 1)
            {
                appSettings.DataMap = dataMap.ToArray();
                return appSettings;
            }
            roqlFileTemplate = fileResults[0].RNObjectsResult[0] as RnConnSvc.GenericObject;
                       
            foreach (RnConnSvc.GenericField field in roqlFileTemplate.GenericFields)
            {
                switch (field.name)
                {
                    case "template_name":
                        appSettings.TmplFile = field.DataValue.Items[0].ToString();
                        break;
                    case "template_directory":
                        appSettings.TmplDir = field.DataValue.Items[0].ToString();
                        break;
                    case "output_format":
                        appSettings.FileFormat = field.DataValue.Items[0].ToString(); ;
                        break;
                    case "output_as_pdf":
                        appSettings.Pdf = (bool)field.DataValue.Items[0];
                        break;
                    case "send_to_printer":
                        appSettings.AutoPrint = (bool)field.DataValue.Items[0];
                        break;
                    case "output_directory":
                        appSettings.OutputDir = field.DataValue.Items[0].ToString();
                        break;
                    case "attach_to_record":
                        appSettings.AttachToContact = (bool)field.DataValue.Items[0];
                        break;
                }
            }

            foreach (RnConnSvc.GenericObject result in fieldResults[0].RNObjectsResult)
            {
                DataMapItem currentRecord = new DataMapItem();
                foreach (RnConnSvc.GenericField resultField in result.GenericFields)
                {
                    if (resultField.name == "merge_field")
                    {
                        currentRecord.TmplFld = resultField.DataValue.Items[0].ToString();
                    }
                    else if (resultField.name == "record_field")
                    {
                        currentRecord.RntFld = resultField.DataValue.Items[0].ToString();
                    }
                }
                dataMap.Add(currentRecord);
            }
            appSettings.DataMap = dataMap.ToArray();
            return appSettings;
        }


      

        /**********************************************Private Functions**********************************************/

        private RnConnSvc.GenericObject getGenericObjectTemplate(string ObjNamespace, string ObjTypeName, long obj_id)
        {
            RnConnSvc.GenericObject pledgeTemplate = new RnConnSvc.GenericObject();
            pledgeTemplate.ObjectType = new RnConnSvc.RNObjectType();
            pledgeTemplate.ObjectType.Namespace = ObjNamespace;
            pledgeTemplate.ObjectType.TypeName = ObjTypeName;
            pledgeTemplate.ID = new RnConnSvc.ID();
            pledgeTemplate.ID.id = obj_id;
            pledgeTemplate.ID.idSpecified = true;
            return pledgeTemplate;
        }

        private RnConnSvc.GenericObject getGenericIncident(int i_id)
        {
            RnConnSvc.GenericObject genericIncident = new RnConnSvc.GenericObject();
            RnConnSvc.ID inc_id = new RnConnSvc.ID();
            inc_id.id = i_id;
            inc_id.idSpecified = true;
            genericIncident.ID = inc_id;
            RnConnSvc.RNObjectType incType = new RnConnSvc.RNObjectType();
            incType.TypeName = "Incident";
            genericIncident.ObjectType = incType;
            return genericIncident;
        }

        private RnConnSvc.GenericObject getGenericContact(long c_id)
        {
            RnConnSvc.GenericObject genericContact = new RnConnSvc.GenericObject();
            RnConnSvc.ID contactId = new RnConnSvc.ID();
            contactId.id = c_id;
            contactId.idSpecified = true;
            genericContact.ID = contactId;

            RnConnSvc.RNObjectType conType = new RnConnSvc.RNObjectType();
            conType.TypeName = "Contact";
            genericContact.ObjectType = conType;
            return genericContact;
        }
        /**
         * Parses a generic object into a name/value dictionary that contains all fields and their
         * associated values
         */
        private SortedDictionary<string, string> parseGenericObject(RnConnSvc.GenericObject objToParse, string entryPrefix)
        {
            SortedDictionary<string, string> objDict = new SortedDictionary<string, string>();
            return this.parseGenericObject(objToParse, entryPrefix, objDict);
        }

        /**
         * parses a generic object into name/value field pairs, and adds the results into the passed dictionary
         * 
         * This is exposed to the rest of the class, rather than using a nested delegate so that dictionaries from
         * disparate sources may be combined without extensive collision handling.
         */
        private SortedDictionary<string, string> parseGenericObject(RnConnSvc.GenericObject objToParse, string entryPrefix, SortedDictionary<string, string> existingDict)
        {
            if (objToParse.GenericFields == null)
            {
                return existingDict;
            }
            foreach (RnConnSvc.GenericField field in objToParse.GenericFields)
            {
                if (field.DataValue == null || field.DataValue.Items[0] == null)
                {
                    existingDict.Add(entryPrefix + field.name, "");
                    continue;
                }
                switch (field.dataType)
                {

                    case RnConnSvc.DataTypeEnum.STRING:
                        existingDict.Add(entryPrefix + field.name, (string)field.DataValue.Items[0]);
                        break;
                    case RnConnSvc.DataTypeEnum.NAMED_ID:
                        existingDict.Add(entryPrefix + field.name, ((RnConnSvc.NamedID)field.DataValue.Items[0]).Name);
                        break;
                    case RnConnSvc.DataTypeEnum.BOOLEAN:
                        string response = (bool)field.DataValue.Items[0] ? "True" : "False";
                        existingDict.Add(entryPrefix + field.name, response);
                        break;
                    case RnConnSvc.DataTypeEnum.DATETIME:
                        response = ((DateTime)field.DataValue.Items[0]).ToString();
                        existingDict.Add(entryPrefix + field.name, response);
                        break;
                    case RnConnSvc.DataTypeEnum.OBJECT:
                        existingDict = this.parseGenericObject((RnConnSvc.GenericObject)field.DataValue.Items[0], entryPrefix + field.name + ": ", existingDict);
                        break;
                    case RnConnSvc.DataTypeEnum.OBJECT_LIST:
                        foreach (RnConnSvc.GenericObject objListObj in field.DataValue.Items)
                        {
                            existingDict = this.parseGenericObject(objListObj, entryPrefix, existingDict);
                        }
                        break;
                    case RnConnSvc.DataTypeEnum.NAMED_ID_HIERARCHY:
                        if(!(field.DataValue.Items[0] is RnConnSvc.NamedIDHierarchy))
                        {
                            break;
                        }

                        int numLevels = 0;
                        if (((RnConnSvc.NamedIDHierarchy)field.DataValue.Items[0]).Parents != null)
                        {
                            foreach (RnConnSvc.NamedReadOnlyID parent in ((RnConnSvc.NamedIDHierarchy)field.DataValue.Items[0]).Parents)
                            {
                                existingDict.Add(entryPrefix + field.name + ": level " + numLevels.ToString(), parent.Name);
                                numLevels++;
                            }
                        }
                        existingDict.Add(entryPrefix+field.name +": level "+numLevels.ToString(), ((RnConnSvc.NamedIDHierarchy)field.DataValue.Items[0]).Name);
                        existingDict.Add(entryPrefix + field.name + ": lowest level" , ((RnConnSvc.NamedIDHierarchy)field.DataValue.Items[0]).Name);
                        break;
                }

            }
            return existingDict;
        }

       


        private void _addFileToIncident(int i_id, string userFName, string fileLocation, string mimeType)
        {
            if (!this.initialized)
            {
                throw new InvalidOperationException(this.uninitializedErrorMsg);
            }
            RnConnSvc.Incident newInc = new RnConnSvc.Incident();
            newInc.ID = new RnConnSvc.ID
            {
                id = i_id,
                idSpecified = true
            };
            RnConnSvc.FileAttachmentIncident[] fattach = new RnConnSvc.FileAttachmentIncident[1];
            fattach[0] = new RnConnSvc.FileAttachmentIncident
            {
                action = RnConnSvc.ActionEnum.add,
                actionSpecified = true,
                ContentType = mimeType,
                Data = this._ReadByteArrayFromFile(fileLocation),
                Description = "Mail Merge Attachment",
                FileName = userFName,
                Name = userFName
            };
            newInc.FileAttachments = fattach;

            RnConnSvc.UpdateProcessingOptions updateOpts = new RnConnSvc.UpdateProcessingOptions
            {
                SuppressExternalEvents = false,
                SuppressRules = false
            };

            //let exceptions bubble up
            RnConnSvc.RNObject[] updateObjects = new RnConnSvc.RNObject[] { newInc };
            this.rnService.Update(this.rnClientInfoHeader, updateObjects, updateOpts);
        }

        private void _addFileToObject(int obj_id, string package, string objName, string userFName, string fileLocation, string mimeType)
        {
            if (!this.initialized)
            {
                throw new InvalidOperationException(this.uninitializedErrorMsg);
            }
            RnConnSvc.GenericObject updateObj = new RnConnSvc.GenericObject
            {
                ObjectType = new RnConnSvc.RNObjectType
                {
                    Namespace = package,
                    TypeName = objName
                },
                GenericFields =
                    new RnConnSvc.GenericField[] {this.getFileAttachmentfield(fileLocation, userFName, mimeType)},
                ID = new RnConnSvc.ID
                {
                    id = obj_id,
                    idSpecified = true
                }
            };
            RnConnSvc.UpdateProcessingOptions updateOpts = new RnConnSvc.UpdateProcessingOptions
            {
                SuppressExternalEvents = false,
                SuppressRules = false
            };

            //let exceptions bubble up
            RnConnSvc.RNObject[] updateObjects = new RnConnSvc.RNObject[] { updateObj };
            this.rnService.Update(this.rnClientInfoHeader, updateObjects, updateOpts);
        }


        /**
         * Given a file name, read the file from disk and returns it as a byte array.
         */
        /// <summary>
        /// Read a file into a byte array.
        /// http://kseesharp.blogspot.com/2007/12/read-file-into-byte-array.html
        /// </summary>
        /// <param name="fileName">The path and name of the file to read into a byte array.</param>
        /// <returns>The array of byte's for the file specified.</returns>
        private byte[] _ReadByteArrayFromFile(string fileName)
        {
            byte[] buff = null;
            FileStream fs = new FileStream(fileName, FileMode.Open, FileAccess.Read);
            BinaryReader br = new BinaryReader(fs);
            long numBytes = new FileInfo(fileName).Length;

            buff = br.ReadBytes((int)numBytes);
            fs.Close();
            return buff;
        }

        private RnConnSvc.GenericField getFileAttachmentfield(string fileLoc, string userFName, string mimeType)
        {
            byte[] fileData = this._ReadByteArrayFromFile(fileLoc);
            RnConnSvc.GenericObject fattachObj = new RnConnSvc.GenericObject()
            {
                ObjectType = new RnConnSvc.RNObjectType() { TypeName = "FileAttachmentIncident" },
                GenericFields = new RnConnSvc.GenericField[]
                                    {
                                    // add "ContentType"
                                    new RnConnSvc.GenericField()
                                    {
                                        name = "ContentType",
                                        DataValue = new RnConnSvc.DataValue()
                                        {
                                            ItemsElementName = new RnConnSvc.ItemsChoiceType[] { RnConnSvc.ItemsChoiceType.StringValue },
                                            Items = new object[] {mimeType}
                                        }
                                    },
                                    // add "FileName"
                                    new RnConnSvc.GenericField()
                                    {
                                        name = "FileName",
                                        DataValue = new RnConnSvc.DataValue()
                                        {
                                            ItemsElementName = new RnConnSvc.ItemsChoiceType[] { RnConnSvc.ItemsChoiceType.StringValue },
                                            Items = new object[] { userFName }
                                        }
                                    },
                                    // add "ContentType"
                                    new RnConnSvc.GenericField()
                                    {
                                        name = "Data",
                                        DataValue = new RnConnSvc.DataValue()
                                        {
                                            ItemsElementName = new RnConnSvc.ItemsChoiceType[] { RnConnSvc.ItemsChoiceType.Base64BinaryValue },
                                            Items = new object[] { fileData }
                                        }
                                    }
                                    }
            };
            RnConnSvc.GenericField fAttachField = new RnConnSvc.GenericField()
            {
                name = "FileAttachments",
                DataValue = new RnConnSvc.DataValue()
                {
                    ItemsElementName = new RnConnSvc.ItemsChoiceType[] { RnConnSvc.ItemsChoiceType.ObjectValueList },
                    Items = new RnConnSvc.GenericObject[] { fattachObj }
                }
            };
            return fAttachField;
        }

              

        private List<RnConnSvc.BatchRequestItem> createTemplateBatchCreateObj(Setting currentSettings, List<RnConnSvc.BatchRequestItem> requests)
        {
            string chainSourceVarName= "newTemplateFileId";
            RnConnSvc.GenericObject settingsTemplate = new RnConnSvc.GenericObject()
            {
                ObjectType = new RnConnSvc.RNObjectType()
                {
                    Namespace = "mail_merge",
                    TypeName = "template_file"
                }
            };
            List<RnConnSvc.GenericField> gfs = new List<RnConnSvc.GenericField>
            {
                this.createGenericStringField("template_name", currentSettings.TmplFile),
                this.createGenericStringField("template_directory", currentSettings.TmplDir),
                this.createGenericBoolField("attach_to_record", currentSettings.AttachToContact),
                this.createGenericBoolField("output_as_pdf", currentSettings.Pdf),
                this.createGenericBoolField("send_to_printer", currentSettings.AutoPrint),
                this.createGenericStringField("output_format", currentSettings.FileFormat),
                this.createGenericStringField("output_directory", currentSettings.OutputDir)
            };
            settingsTemplate.GenericFields = gfs.ToArray();

            RnConnSvc.ChainSourceID chainTemplateId = new RnConnSvc.ChainSourceID();
            chainTemplateId.variableName = chainSourceVarName;
            settingsTemplate.ID = chainTemplateId;

            RnConnSvc.CreateProcessingOptions createProcessingOptions = new RnConnSvc.CreateProcessingOptions();
            createProcessingOptions.SuppressExternalEvents = false;
            createProcessingOptions.SuppressRules = false;

            RnConnSvc.BatchRequestItem requestItem = new RnConnSvc.BatchRequestItem()
            {
                Item = new RnConnSvc.CreateMsg()
                {
                    ProcessingOptions = createProcessingOptions,
                    RNObjects = new RnConnSvc.RNObject[] { settingsTemplate }
                }
            };
            requests.Add(requestItem);


            //add all of our field mappings
            foreach (com.rightnow.MailMerge.WebService.DataMapItem item in currentSettings.DataMap)
            {
                if (item.RntFld == null || item.RntFld.Length < 1 || item.TmplFld == null || item.TmplFld.Length < 1)
                {
                    continue;
                }
                requestItem = createFieldMapBatchCreateObj(chainSourceVarName, item);
                requests.Add(requestItem);
            }
            return requests;
        }

        private RnConnSvc.BatchRequestItem createFieldMapBatchCreateObj(string chainTemplateVar, com.rightnow.MailMerge.WebService.DataMapItem item)
        {
            RnConnSvc.GenericObject fieldMapping = new RnConnSvc.GenericObject()
            {
                ObjectType = new RnConnSvc.RNObjectType()
                {
                    Namespace = "mail_merge",
                    TypeName = "field_map"
                }
            };

            List<RnConnSvc.GenericField> fieldData = new List<RnConnSvc.GenericField>();
            fieldData.Add(this.createGenericStringField("merge_field", item.TmplFld));
            fieldData.Add(this.createGenericStringField("record_field", item.RntFld));
            fieldData.Add(this.createNamedIDChainDataValue(chainTemplateVar, "template_file"));
            fieldMapping.GenericFields = fieldData.ToArray();

            RnConnSvc.CreateProcessingOptions createProcessingOptions = new RnConnSvc.CreateProcessingOptions();
            createProcessingOptions.SuppressExternalEvents = false;
            createProcessingOptions.SuppressRules = false;
            RnConnSvc.BatchRequestItem requestItem = new RnConnSvc.BatchRequestItem()
            {
                Item = new RnConnSvc.CreateMsg()
                {
                    ProcessingOptions = createProcessingOptions,
                    RNObjects = new RnConnSvc.RNObject[] { fieldMapping }
                }
            };
            return requestItem;
        }

        private static RnConnSvc.BatchRequestItem createTemplateBatchDestroyObj(int settingId)
        {
            RnConnSvc.GenericObject oldSettingsTemplate = new RnConnSvc.GenericObject()
            {
                ObjectType = new RnConnSvc.RNObjectType()
                {
                    Namespace = "mail_merge",
                    TypeName = "template_file"
                }
            };
            oldSettingsTemplate.ID = new RnConnSvc.ID
            {
                id = settingId,
                idSpecified = true
            };

            RnConnSvc.DestroyProcessingOptions destroyOptions = new RnConnSvc.DestroyProcessingOptions
            {
                SuppressExternalEvents = false,
                SuppressRules = false
            };

            RnConnSvc.BatchRequestItem requestItem = new RnConnSvc.BatchRequestItem()
            {
                Item = new RnConnSvc.DestroyMsg()
                {
                    ProcessingOptions = destroyOptions,
                    RNObjects = new RnConnSvc.RNObject[] { oldSettingsTemplate }
                }
            };
            return requestItem;
        }

        private List<int> getSettingObjIds(string templateFileName)
        {
            string roql = "select mail_merge.template_file from mail_merge.template_file where mail_merge.template_file.template_name = '" + templateFileName + "'";
            /*          api.rightnow.com.RNOWObjectFactory of = new api.rightnow.com.RNOWObjectFactory(string.Format("{0}xml_api/soap_api.php", AutoClient.globalContext.InterfaceURL));

                      if (of.relogin(AutoClient.globalContext.InterfaceName, AutoClient.globalContext.Login))
                      {
                          obj.api.rightnow.com.RNOWOptlItem[] vals = of.getOptList(82);
                      }
                      */

            RnConnSvc.QueryResultData[] results;

            RnConnSvc.GenericObject roqlFileTemplate = new RnConnSvc.GenericObject();
            roqlFileTemplate.ObjectType = new RnConnSvc.RNObjectType();
            roqlFileTemplate.ObjectType.Namespace = "mail_merge";
            roqlFileTemplate.ObjectType.TypeName = "template_file";
            RnConnSvc.RNObject[] submitObjects = new RnConnSvc.RNObject[] { roqlFileTemplate };
            //let exceptions propagate
            results = this.rnService.QueryObjects(this.rnClientInfoHeader, roql, submitObjects, 1000);
            
            if (results[0].Fault != null)
            {
                throw new Exception(results[0].Fault.exceptionMessage);
            }
            List<int> settingObjs = new List<int>();
            if (results[0].RNObjectsResult.Count() < 1)
            {
                return settingObjs;
            }
            roqlFileTemplate = results[0].RNObjectsResult[0] as RnConnSvc.GenericObject;
            foreach (RnConnSvc.RNObject result in results[0].RNObjectsResult)
            {
                settingObjs.Add((int)result.ID.id);
            }
            return settingObjs;
        }


        private RnConnSvc.GenericField createGenericStringField(string field, string value )
        {
            return new RnConnSvc.GenericField()
            {
                name = field,
                dataType = RnConnSvc.DataTypeEnum.STRING,
                DataValue = new RnConnSvc.DataValue()
                {
                    ItemsElementName = new RnConnSvc.ItemsChoiceType[] { RnConnSvc.ItemsChoiceType.StringValue },
                    Items = new Object[] { value }
                }
            };
        }

        private RnConnSvc.GenericField createGenericBoolField(string field, bool value)
        {
            return new RnConnSvc.GenericField()
            {
                name = field,
                dataType = RnConnSvc.DataTypeEnum.BOOLEAN,
                DataValue = new RnConnSvc.DataValue()
                {
                    ItemsElementName = new RnConnSvc.ItemsChoiceType[] { RnConnSvc.ItemsChoiceType.BooleanValue },
                    Items = new Object[] { value }
                }
            };
        }

        private RnConnSvc.GenericField createNamedIDChainDataValue(String chainId, String fieldName)
        {
            RnConnSvc.GenericField templateFileId = new RnConnSvc.GenericField()
            {
                dataType = RnConnSvc.DataTypeEnum.NAMED_ID,
                dataTypeSpecified = true,
                name = fieldName,
                DataValue = new RnConnSvc.DataValue()
                {
                    ItemsElementName = new RnConnSvc.ItemsChoiceType[] { RnConnSvc.ItemsChoiceType.NamedIDValue },
                    Items = new Object[] {
                    new RnConnSvc.NamedID()
                    {
                        ID = new RnConnSvc.ChainDestinationID()
                        {
                            variableName = chainId
                        }
                    }
                }
                }
            };
            return templateFileId;
        }

        private RnConnSvc.AnalyticsReport getReportData(int acId) //TODO: Determine if we want to return the RN Object here or the report object its self
        {
          
            var analytictsReport = new RnConnSvc.AnalyticsReport();
            //Specify a report ID of Public Reports>Common>Data integration>Opportunities
            RnConnSvc.ID reportID = new RnConnSvc.ID
            {
                id = acId,
                idSpecified = true
            };
            // reportID.id = 13029;
            analytictsReport.ID = reportID;
            RnConnSvc.ClientInfoHeader clientInfoHeader = new RnConnSvc.ClientInfoHeader();
            clientInfoHeader.AppID = "Get an Analytics Report"; //TODO create a constant for this value here, or modify method signature to accept App ID as a parameter 
            //Create a filter and specify the filter name
            //Assigning the filter created in desktop agent to the new analyticts filter 
            RnConnSvc.AnalyticsReportFilter filter = new RnConnSvc.AnalyticsReportFilter();

            //Apply the filter to the AnalyticsReport object
            analytictsReport.Filters = (new RnConnSvc.AnalyticsReportFilter[] { filter });
            RnConnSvc.GetProcessingOptions processingOptions = new RnConnSvc.GetProcessingOptions {FetchAllNames = true};

            RnConnSvc.RNObject[] getAnalyticsObjects = new RnConnSvc.RNObject[] { analytictsReport };
            try
            {
                rnService.Open();
                RnConnSvc.RNObject[] rnObjects = rnService.Get(clientInfoHeader, getAnalyticsObjects, processingOptions);
                rnService.Close();
                analytictsReport = (RnConnSvc.AnalyticsReport)rnObjects[0];
            }
            catch (Exception ex)
            {
                System.Windows.Forms.MessageBox.Show(ex.Message.ToString(), "Error" + ex.Message + ex.InnerException); //TODO: Determine if more descriptive and location oriented error messages are desired here.
            }
            
            return analytictsReport;
        }


    }
}