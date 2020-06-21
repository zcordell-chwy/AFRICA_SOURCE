using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using ccProcessing.cws;
using RightNow.AddIns.AddInViews;
using System.ServiceModel;
using System.ServiceModel.Channels;

namespace ccProcessing
{
   public  class cwsModel
    {
        private RightNowSyncPortClient _service;
        private IGlobalContext _context;
        ClientInfoHeader header;
        public List<String> errors = new List<string>();
        public Dictionary<string, List<GenericObject>> roqlCache = new Dictionary<string, List<GenericObject>>();
        public Dictionary<string, Dictionary<long,NamedID>> namedIdCache = new Dictionary<string,Dictionary<long,NamedID>>();
        public Dictionary<string, List<paymentMethod>> paymentMethodCache = new Dictionary<string, List<paymentMethod>>();

        public RightNowSyncPortClient Service
        {
            get
            {
                return this._service;
            }
            set
            {
                this._service = value;
            }
        }

        public cwsModel(IGlobalContext context)
        {
            this._context = context;
            EndpointAddress endPointAddr = new EndpointAddress(_context.GetInterfaceServiceUrl(ConnectServiceType.Soap));
            BasicHttpBinding binding = new BasicHttpBinding(BasicHttpSecurityMode.TransportWithMessageCredential);
            binding.Security.Message.ClientCredentialType = BasicHttpMessageCredentialType.UserName;

            // Optional depending upon use cases
            binding.MaxReceivedMessageSize = 65536000;
            binding.MaxBufferSize = 65536000;
            binding.MessageEncoding = WSMessageEncoding.Mtom;

            // Create client proxy class
            RightNowSyncPortClient client = new RightNowSyncPortClient(binding, endPointAddr);

            // Ask the client to not send the timestamp
            BindingElementCollection elements = client.Endpoint.Binding.CreateBindingElements();
            elements.Find<SecurityBindingElement>().IncludeTimestamp = false;
            client.Endpoint.Binding = new CustomBinding(elements);

            // Ask the Add-In framework the handle the session logic
            _context.PrepareConnectSession(client.ChannelFactory);

            this.Service = client;

            this.header = new ClientInfoHeader { AppID = "CC Processing" };
          
        }


        public string getTransactionStatusbyDonationId(long donationId)
        {
            GenericObject trans = getTransactionByDonationId(donationId, false);
            if (trans == null)
            {
                return "";
            }
            
            GenericField status = trans.GenericFields.Where(field => field.name.Equals("currentStatus")).First();
            if (status.DataValue == null)
            {
                return "";
            }
            NamedID statusObj = status.DataValue.Items[0] as NamedID;

            Dictionary<long, NamedID> statusDict = this.getNameIdValues("financial", "transactions.currentStatus");
            if (statusObj == null)
            {
                return "";
            }

            NamedID statusStr;
            if (statusDict.TryGetValue(statusObj.ID.id, out statusStr))
            {
                return statusStr.Name;
            }
            return "";
            
        }

                


        /**
         * 
         */

       public long createOrUpdateTransactionObj(long donationId, string newNote)
        {
            return this.createOrUpdateTransactionObj(donationId, newNote, -1, "", -1, null);
        }

       /**
        * 
        */
        public long createOrUpdateTransactionObj(long donationId, string newNote, float amount, string status, long paymentMethod, string PNRef)
        {
            return createOrUpdateTransactionObj(donationId, newNote, amount, status, paymentMethod, false, PNRef);
        }
       /**
        * Create or update a transaction.  There should only ever be a single transaction per donation.
        * Changes to the transaction should be noted in the notes section
        */
       public long createOrUpdateTransactionObj(long donationId, string newNote, float amount, string status, long paymentMethod, bool suppress, string PNRef)
        {
            bool isCreate = true;
            if (donationId < 1)
            {
                return -1;
            }

            //figure out if we're create or update
            GenericObject trans = getTransactionByDonationId(donationId, false);
            if (trans == null)
            {
                trans = getTransactionGenericObj(donationId);
            }
            else
            {
                isCreate = false;
                trans = getTransactionGenericObj(donationId, trans.ID);
            }

            List<GenericField> fields = trans.GenericFields.ToList();

            //zc 1/5/16
            if (PNRef != null && PNRef.Length > 0)
            {
                fields.Add(createGenericField("refCode", createStringDataValue(PNRef), DataTypeEnum.STRING));
            }

            //add a new note to the transaction
            if (newNote != null && newNote.Length > 0)
            {
                
                GenericField textGenericField = createGenericField("Text", createStringDataValue(newNote), DataTypeEnum.STRING);

                GenericField actionEnumGenericField = createGenericField("ActionEnum", createStringDataValue("add"), DataTypeEnum.STRING);
                GenericField[] actionFieldList = new GenericField[] { actionEnumGenericField };
                GenericField actionGenericField = createGenericField("action", createObjectDataValue("ActionEnum", actionFieldList), DataTypeEnum.OBJECT);

                GenericField[] noteGenericFieldList = new GenericField[] { actionGenericField, textGenericField };
                GenericObject[] noteGnericObjectArray = new GenericObject[] { createGenericObject("Note", noteGenericFieldList) };
                GenericField noteListGenericField = createGenericField("Notes", createObjectListDataValue(noteGnericObjectArray), DataTypeEnum.OBJECT_LIST);

                fields.Add(noteListGenericField);
                
            }

           //update amount
            if (amount > 0)
            {
                fields.Add(createGenericField("totalCharge", createStringDataValue(amount.ToString("F")), DataTypeEnum.STRING));
            }

            //update status
            if (status != null && status.Length > 0)
            {
                fields.Add(createGenericField("currentStatus", createNamedIDDataValue(status), DataTypeEnum.NAMED_ID));
            }

            //update payment Method
            if (paymentMethod > 0)
            {
                fields.Add(createGenericField("paymentMethod", createNamedIDDataValue(paymentMethod), DataTypeEnum.NAMED_ID));
            }

            trans.GenericFields = fields.ToArray();
            
            long returnval =  createOrUpdateObject(isCreate, trans, suppress);
            
            //invalidate the cache
            if (returnval > 0)
            {
                getTransactionByDonationId(donationId, true);
            }
            return returnval;

        }

        public List<paymentMethod> getPaymentMethods(long contactId)
        {
            return getPaymentMethods(contactId, false);
        }


       /**
        * Returns all the valid payment methods for a passed contact
        */
        public List<paymentMethod> getPaymentMethods(long contactId, Boolean clearCache)
        {
            List<paymentMethod> returnPaymentMethods;

            if (contactId < 1)
            {
                return new List<paymentMethod>();
            }

            int currentYear = System.DateTime.Now.Year;
            int currentMonth = System.DateTime.Now.Month;

            string roql = "Select ID, CardType, PN_Ref, expMonth, expYear, lastFour, PaymentMethodType" +
                            " from financial.paymentMethod  " +
                            " where Contact = " + contactId +
                                " and (Inactive is null or Inactive == 0)";
                                //" and (((expYear > '" + currentYear + "') or (expYear = '" + currentYear + "' and expMonth >= '" + currentMonth + "')) or (PaymentMethodType = 2))";
            if (clearCache && this.paymentMethodCache.Keys.Contains(roql))
            {
                this.paymentMethodCache.Remove(roql);
            }

            //see if we can save a lookup
            if (this.paymentMethodCache.TryGetValue(roql, out returnPaymentMethods))
            {
                return returnPaymentMethods;
            }

            returnPaymentMethods = new List<paymentMethod>();
            try
            {
                //   QueryResultData[] results = this._service.QueryObjects(this.header, roql, new RNObject[] { }, 100);
                byte[] byteArray;
                CSVTableSet results = this._service.QueryCSV(this.header, roql, 10000, ",", false, true, out byteArray);
                CSVTable[] csvTables = results.CSVTables;

                foreach (CSVTable table in csvTables)
                {
                    String[] rowData = table.Rows;
                    foreach (String data in rowData)
                    {
                        //" and (((expYear > '" + currentYear + "') or (expYear = '" + currentYear + "' and expMonth >= '" + currentMonth + "')) or (PaymentMethodType = 2))";
                        string[] returnedValues = data.Split(',');
                        
                        int expYear;
                        int.TryParse(returnedValues[4], out expYear);
                        int expMonth;
                        int.TryParse(returnedValues[3], out expMonth);
                        int paymentMethodType;
                        int.TryParse(returnedValues[6], out paymentMethodType);

                        if (((expYear > currentYear) || (expYear == currentYear && expMonth >= currentMonth)) || paymentMethodType == 2)
                        {
                            paymentMethod method = new paymentMethod();
                            method.cardType = returnedValues[1];
                            method.id = returnedValues[0];
                            method.pnRef = returnedValues[2];
                            method.expMonth = returnedValues[3];
                            method.expYear = returnedValues[4];
                            method.lastFour = returnedValues[5];
                            returnPaymentMethods.Add(method);
                        }
                    }
                }

            }
            catch (Exception e)
            {
                this.errors.Add(e.Message);
                return new List<paymentMethod>();
            }

            this.paymentMethodCache.Add(roql, returnPaymentMethods);
            return returnPaymentMethods;
        }

       /**
        * 
        * 
        */
        public long createOrUpdatePaymentMethod(string methodType, long contact, string pnRef, string lastFour, long paymentMethodId, string cardType, string expMonth, string expYear)
        {
            List<GenericField> fields = new List<GenericField>();
            if (methodType != null && methodType.Length > 0)
            {
                fields.Add(createGenericField("PaymentMethodType", createNamedIDDataValue(methodType),DataTypeEnum.NAMED_ID));
            }

            if (contact > 0)
            {
                fields.Add(createGenericField("Contact", createNamedIDDataValue(contact), DataTypeEnum.NAMED_ID));
            }

            if (pnRef != null && pnRef.Length > 0)
            {
                fields.Add(createGenericField("PN_Ref", createStringDataValue(pnRef), DataTypeEnum.STRING));
            }

            if (lastFour != null && lastFour.Length > 0)
            {
                fields.Add(createGenericField("lastFour", createStringDataValue(lastFour), DataTypeEnum.LONG));
            }

            if (cardType != null && cardType.Length > 0)
            {
                fields.Add(createGenericField("CardType", createStringDataValue(cardType), DataTypeEnum.STRING));
            }

            if (expMonth != null && expMonth.Length > 0)
            {
                fields.Add(createGenericField("expMonth", createStringDataValue(expMonth), DataTypeEnum.STRING));
            }

            if (expYear != null && expYear.Length > 0)
            {
                fields.Add(createGenericField("expYear", createStringDataValue(expYear), DataTypeEnum.STRING));
            }
            


            GenericObject pm = createGenericObject("paymentMethod", "financial", fields.ToArray());

            if (paymentMethodId > 0)
            {
                pm.ID = new ID()
                {
                    id = paymentMethodId,
                    idSpecified = true
                };
            }
            bool isCreate = (paymentMethodId > 0) ? false : true;
            long newObjectId = createOrUpdateObject(isCreate, pm);
            if (newObjectId > 0)
            {
                //refresh the cache
                this.getPaymentMethods(contact, true);
            }
            return newObjectId;
        }

        private long createOrUpdateObject(bool isCreate, GenericObject genObj)
        {
            return createOrUpdateObject(isCreate, genObj, false);
        }
        private long createOrUpdateObject(bool isCreate, GenericObject genObj, bool suppress)
        {

            RNObject[] genObjArr = new RNObject[] { genObj };
            RNObject[] retObjs = null;
            try
            {
                if (isCreate)
                {
                    CreateProcessingOptions createProcOpts = new CreateProcessingOptions
                    {
                        SuppressExternalEvents = suppress,
                        SuppressRules = suppress
                    };
                    retObjs = this.Service.Create(header, genObjArr, createProcOpts);
                }
                else
                {
                    UpdateProcessingOptions updateProcOpts = new UpdateProcessingOptions
                    {
                        SuppressExternalEvents = suppress,
                        SuppressRules = suppress
                    };
                    this.Service.Update(header, genObjArr, updateProcOpts);
                }
            }
            catch (Exception e)
            {
                return -1;
            }
            //maks sure we're successfull
            if (isCreate)
            {
                return retObjs[0].ID.id;
            }
            else
            {
                return genObj.ID.id;
            }
        }


       /**
        * get a named id lookup dictionary.  Uses caching
        * 
        */
       private Dictionary<long, NamedID> getNameIdValues(string package, string namedIdInputStr)
        {
            Dictionary<long, NamedID> namedIdDict ;
            if (this.namedIdCache.TryGetValue(package + "." + namedIdInputStr, out namedIdDict))
            {
                return namedIdDict;
            }
            namedIdDict = new Dictionary<long, NamedID>();           
           try
            {
                NamedID[] valuesForNamedId = this.Service.GetValuesForNamedID(this.header, package, namedIdInputStr);
                foreach (NamedID item in valuesForNamedId)
                {
                    namedIdDict.Add(item.ID.id, item);
                }
                this.namedIdCache.Add(package + "." + namedIdInputStr, namedIdDict);
            }
            catch (Exception Ex)
            {
                return null;
            }
            return namedIdDict;
        }

       /**
        * 
        * 
        */
        private MetaDataClass GetMetaDataForCustomObject(string ns, string type)
        {
            RNObjectType rnObjectType = new RNObjectType();
            rnObjectType.Namespace = ns;
            rnObjectType.TypeName = type;
            MetaDataClass[] metaDataClasses ;
            try
            {
                metaDataClasses = this.Service.GetMetaDataForClass(this.header, null, new RNObjectType[] { rnObjectType }, null);
            }
            catch
            {
                return null;
            }

            if (metaDataClasses.Count() > 0)
            {
                return metaDataClasses[0];
            }
            else
            {
                return null;
            }
        }

       


        private static GenericObject getTransactionGenericObj(long donationId)
        {
           return getTransactionGenericObj(donationId, null);
        }

        private static GenericObject getTransactionGenericObj(long donationId, ID id)
        {
            GenericObject trans = new GenericObject
            {
                ObjectType = new RNObjectType
                {
                    TypeName = "transactions",
                    Namespace = "financial"
                },
                GenericFields = new GenericField[] { 
                    new GenericField{
                        dataType = DataTypeEnum.ID,
                        dataTypeSpecified = true,
                        DataValue = new DataValue{
                            Items = new Object[]{
                                new NamedID{
                                  ID =
                                  new ID{
                                    id = donationId,
                                    idSpecified = true
                                  }
                                }
                            },
                            ItemsElementName = new ItemsChoiceType[]{ItemsChoiceType.NamedIDValue},                            
                        },
                        name = "donation"                            
                    }
                }
            };
            if (id != null)
            {
                trans.ID = id;
            }
            return trans;
        }




        public aggregateTransactionData getTransactionByDonationId(long donationID, long contactId)
        {

            //this transaction object doesn't have the updated status we just set.  No need to do a fresh lookup though, we only need payment and ref no info.
            GenericObject dirtyTransaction = getTransactionByDonationId(donationID, false);
            List<paymentMethod> paymentMethods = getPaymentMethods(contactId);
            paymentMethod thisTransPaymentMethod = null;
            if (paymentMethods.Count < 1)
            {
                return null;
            }

            foreach (paymentMethod paymentMethod in paymentMethods)
            {
                GenericField paymentMethodId = dirtyTransaction.GenericFields.Where(field => field.name.Equals("paymentMethod")).First() as GenericField;
                if (paymentMethodId == null)
                {
                    continue;
                }
                NamedID paymentMethodIdFieldId = paymentMethodId.DataValue.Items[0] as NamedID;
                if (paymentMethodIdFieldId == null)
                {
                    continue;
                }
                if (paymentMethod.id == paymentMethodIdFieldId.ID.id.ToString())
                {
                    thisTransPaymentMethod = paymentMethod;
                    break;
                }
                
            }
            if (thisTransPaymentMethod == null)
            {
                return null;
            }

            aggregateTransactionData transObj = new aggregateTransactionData();
            transObj.transObj = dirtyTransaction;
            transObj.paymentMethod = thisTransPaymentMethod;

            return transObj;


        }

        public GenericObject getTransactionByDonationId(long donationID, Boolean clearCache)
        {

            string roql = "Select financial.transactions from financial.transactions where donation = " + donationID;


            if (!clearCache)
            {
                List<GenericObject> cachedTrans;
                if (this.roqlCache.TryGetValue(roql, out cachedTrans))
                {
                    return cachedTrans[0];
                }
            }
            else
            {
                if (this.roqlCache.Keys.Contains(roql))
                {
                    this.roqlCache.Remove(roql);
                }

            }


            RNObject[] templates = new RNObject[] { getTransactionGenericObj(donationID) };
            QueryResultData[] queryObjs;
            try
            {
                queryObjs = this._service.QueryObjects(this.header, roql, templates, 10000);
            }
            catch (Exception e)
            {
                return null;
            }
            if (queryObjs.Count() > 0)
            {
                if (queryObjs[0].RNObjectsResult.Count() > 0)
                {
                    this.roqlCache.Add(roql, new List<GenericObject> { (GenericObject)queryObjs[0].RNObjectsResult[0] });
                    return (GenericObject)queryObjs[0].RNObjectsResult[0];
                }
                else
                {
                    this.roqlCache.Add(roql, new List<GenericObject> { null });

                }

            }
            return null;
        }


        private DataValue createObjectDataValue(String typeName, GenericField[] genericFields)
        {
            DataValue dv = new DataValue();
            dv.Items = new Object[] { createGenericObject(typeName, genericFields) };
            dv.ItemsElementName = new ItemsChoiceType[] { ItemsChoiceType.ObjectValue };

            return dv;
        }

        private GenericObject createGenericObject(String typeName, String package, GenericField[] genericFields)
        {
            GenericObject genericObject = new GenericObject();
            genericObject.ObjectType = createRNObjectType(typeName, package);
            genericObject.GenericFields = genericFields;

            return genericObject;
        }

        private GenericObject createGenericObject(String typeName, GenericField[] genericFields)
        {
            return createGenericObject(typeName, null, genericFields);
        }


        private RNObjectType createRNObjectType(String typeName)
        {
            return createRNObjectType(typeName, null);
        }

        private RNObjectType createRNObjectType(String typeName, String package)
        {
            RNObjectType objType = new RNObjectType();
            objType.TypeName = typeName;
            if (package != null && package.Length > 0)
            {
                objType.Namespace = package;
            }

            return objType;
        }

        private GenericField createGenericField(String name, DataValue dataValue, DataTypeEnum type)
        {
            GenericField genericField = new GenericField();

            genericField.dataType = type;
            genericField.dataTypeSpecified = true;
            genericField.name = name;
            genericField.DataValue = dataValue;

            return genericField;
        }

        private DataValue createStringDataValue(String val)
        {
            DataValue dv = new DataValue();
            dv.Items = new Object[] { val };
            dv.ItemsElementName = new ItemsChoiceType[] { ItemsChoiceType.StringValue };

            return dv;
        }

        private DataValue createLongDataValue(long val)
        {
            DataValue dv = new DataValue();
            dv.Items = new Object[] { val };
            dv.ItemsElementName = new ItemsChoiceType[] { ItemsChoiceType.LongValue };
            return dv;
        }

        private DataValue createIntDataValue(int val)
        {
            DataValue dv = new DataValue();
            dv.Items = new Object[] { val };
            dv.ItemsElementName = new ItemsChoiceType[] { ItemsChoiceType.IntegerValue };
            return dv;
        }
        private DataValue createObjectListDataValue(GenericObject[] genericObjects)
        {
            DataValue dv = new DataValue();
            dv.Items = genericObjects;
            dv.ItemsElementName = new ItemsChoiceType[] { ItemsChoiceType.ObjectValueList };

            return dv;
        }

        private DataValue createNamedIDDataValue(long idVal)
        {
            ID id = new ID();
            id.id = idVal;
            id.idSpecified = true;

            NamedID namedID = new NamedID();
            namedID.ID = id;

            DataValue dv = new DataValue();
            dv.Items = new Object[] { namedID };
            dv.ItemsElementName = new ItemsChoiceType[] { ItemsChoiceType.NamedIDValue };

            return dv;
        }

        private DataValue createNamedIDDataValue(string label)
        {
            
            NamedID namedID = new NamedID();
            namedID.Name = label;

            DataValue dv = new DataValue();
            dv.Items = new Object[] { namedID };
            dv.ItemsElementName = new ItemsChoiceType[] { ItemsChoiceType.NamedIDValue };

            return dv;
        }

        
    }
}
