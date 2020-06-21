using System;
using System.IO;
using System.Net;
using System.Text;
using System.Net.Security;
using System.Security.Cryptography.X509Certificates;
using System.Collections.Generic;
using System.Runtime.Serialization;
using System.Runtime.Serialization.Json;
using MailMergeAddIn.RightNowService;
using MailMergeAddIn.RnConnSvc;

namespace com.rightnow.MailMerge.WebService
{
    public class Request
    {
        /// <summary>
        /// Sets an object as a json string.
        /// </summary>
        /// <param name="objToSerialize">The object to serialize.</param>
        /// <returns></returns>
        public static string toJsonString(object objToSerialize)
        {
            using (MemoryStream ms = new MemoryStream())
            {
                DataContractJsonSerializer ser = new DataContractJsonSerializer(objToSerialize.GetType());
                ser.WriteObject(ms, objToSerialize);
                ms.Position = 0;
                using (StreamReader sr = new StreamReader(ms))
                {
                    return sr.ReadToEnd();
                }
            }
        }

        // use generics to deserialize, however Setting[] is likely the only thing we will be passing
        /// <summary>
        /// Returns an object From a json string.
        /// </summary>
        /// <typeparam name="T"></typeparam>
        /// <param name="jsonStr">The json string.</param>
        /// <returns></returns>
        public static T fromJsonString<T>(string jsonStr)
        {
            using (MemoryStream ms = new MemoryStream(Encoding.UTF8.GetBytes(jsonStr)))
            {
                DataContractJsonSerializer ser = new DataContractJsonSerializer(typeof(T));
                return (T)ser.ReadObject(ms);
            }
        }
        
        [Obsolete("This method has no references to it as reports are now located by report id, in the updated Right Now Services")]
        public static Report[] getReportsByProfileId(string url)
        {
            try
            {
                string response = Request.getResponse(url);
                MemoryStream ms = new MemoryStream(Encoding.UTF8.GetBytes(response));
                DataContractJsonSerializer ser = new DataContractJsonSerializer(typeof(Report[]));
                Report[] reports = (Report[])ser.ReadObject(ms);

                return reports;
            }
            catch (Exception ex)
            {
                System.Windows.Forms.MessageBox.Show(ex.Message, "Exception caught 1");
            }

            return new Report[0];
        }

        [Obsolete("This method has been replaced by the new RightNowService")]
        public static Column[] getAcColumns(string url)
        {
            try
            {
                string response = Request.getResponse(url);
                MemoryStream ms = new MemoryStream(Encoding.UTF8.GetBytes(response));
                DataContractJsonSerializer ser = new DataContractJsonSerializer(typeof(Column[]));
                Column[] columns = (Column[])ser.ReadObject(ms);

                return columns;
            }
            catch (Exception ex)
            {
                System.Windows.Forms.MessageBox.Show(ex.Message, "Exception caught 2" + ex.InnerException + ex.StackTrace);
            }

            return new Column[0];
        }

        /// <summary>
        /// Gets the response.
        /// </summary>
        /// <param name="url">The URL.</param>
        /// <returns></returns>
        private static string getResponse(string url)
        {
            try
            {// accept server certs
                ServicePointManager.ServerCertificateValidationCallback += delegate(object objSender,
                    X509Certificate certificate, X509Chain chain, SslPolicyErrors sslPolicyErrors)
                {
                    return true;
                };

                // connect to web service
                HttpWebRequest req = (HttpWebRequest)WebRequest.Create(url);
                // req.Timeout = 10;
                HttpWebResponse res = (HttpWebResponse)req.GetResponse();

                if (res.StatusCode == HttpStatusCode.OK)
                {
                    // get the response
                    StreamReader respStream = new StreamReader(res.GetResponseStream(), Encoding.UTF8);
                    return respStream.ReadToEnd();
                }
            }
            catch (Exception ex)
            {
                System.Windows.Forms.MessageBox.Show(ex.Message, "Exception caught 3 " + ex.StackTrace );
            }

            return "";
        }

        


    }

    [DataContract]
    public class Setting
    {
        [DataMember(Name = "id")]
        public int Id { get; set; }

        [DataMember(Name = "acct_id")]
        public int AcctId { get; set; }

        [DataMember(Name = "tmpl_file")]
        public string TmplFile { get; set; }

        [DataMember(Name = "tmpl_dir")]
        public string TmplDir { get; set; }

        [DataMember(Name = "output_dir")]
        public string OutputDir { get; set; }

        [DataMember(Name = "file_format")]
        public string FileFormat { get; set; }

        [DataMember(Name = "ac_id")]
        public int AcId { get; set; }

        // TODO: maybe strip off minutes? so it's just day and hour...
        [DataMember(Name = "schedule")]
        public Int64 Schedule { get; set; } // return as epoch timestamp

        [DataMember(Name = "single_doc")]
        public bool SingleDoc { get; set; }

        [DataMember(Name = "auto_print")]
        public bool AutoPrint { get; set; }

        [DataMember(Name = "pdf")]
        public bool Pdf { get; set; }

        [DataMember(Name = "attach_to_contact")]
        public bool AttachToContact { get; set; }

        [DataMember(Name = "email_contacts")]
        public bool EmailContacts { get; set; }

        [DataMember(Name = "data_map")]
        public DataMapItem[] DataMap { get; set; }

        [DataMember(Name = "merge_type")]
        public String MergeType { get; set; }

        [DataMember(Name = "donation_data_r_id")]
        public int DonationDataReportId { get; set; }

        [DataMember(Name = "contact_id_check_passed")]
        public bool ContactIdCheckPassed { get; set; }


        // public List<DataMapItem> DataMap { get; set; }

        // not part of the web service... only used when doing a preview
        public bool IsPreview { get; set; }

        public Setting()
        {
            // set the default value
            IsPreview = false;
        }
    }

    [DataContract]
    public class DataMapItem
    {
        [DataMember(Name = "id")]
        public int Id { get; private set; }

        [DataMember(Name = "setting_id")]
        public int SettingId { get; private set; }

        [DataMember(Name = "rnt_fld")]
        public string RntFld { get; set; }

        [DataMember(Name = "tmpl_fld")]
        public string TmplFld { get; set; }

        // these are not part of the web service... they are only used during merges
        public string Value { get; set; }
        public int ColId { get; set; }
    }

    // this class is read-only, except from server
    [DataContract]
    public class Report
    {
        [DataMember(Name = "ac_id")]
        public int AcId { get; private set; }

        [DataMember(Name = "name")]
        public string Name { get; private set; }
    }

    // this class is read-only, except from server
    [DataContract]
    public class Column
    {
        [DataMember(Name = "ac_id")]
        public int AcId { get; private set; }

        [DataMember(Name = "id")]
        public int Id { get; private set; }

        [DataMember(Name = "val")]
        public string Val { get; set; }
    }
}
