using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Net.Http;
using System;
using System.IO;
using System.Net;
using System.Text;
using System;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Threading.Tasks;
using System.Xml;


namespace ccProcessing
{
    class frontstreamModel
    {
        public frontStreamReturn processCreditCard(string PNRef, string amount, long transId, string transType)
        {

            WebClient client = new WebClient();
            System.Collections.Specialized.NameValueCollection postVals = new System.Collections.Specialized.NameValueCollection();
            postVals.Add("amount", amount);
            postVals.Add("password", serverSettings.Instance.frontstream_apiPassword);
            postVals.Add("username", serverSettings.Instance.frontstream_apiUsername);
            postVals.Add("transType", transType);
            postVals.Add("pnRef", PNRef);

            postVals.Add("MagData", "");
            postVals.Add("ExtData", "");
            postVals.Add("CardNum", "");
            postVals.Add("ExpDate", "");
            postVals.Add("CVNum", "");
            postVals.Add("InvNum", transId.ToString());
            // postVals.Add("InvNum","");
            postVals.Add("NameOnCard", "");
            postVals.Add("Zip", "");
            postVals.Add("Street", "");

            string uri = serverSettings.Instance.frontstream_apiEndpoint + "/ProcessCreditCard";

            byte[] response = client.UploadValues(uri, "POST", postVals);

            frontStreamReturn returnObj = new frontStreamReturn();
            returnObj.rawXmlResponseString = Encoding.UTF8.GetString(response);

            return returnObj;
        }

        public frontStreamReturn processCheck(string PNRef, string amount, string transType)
        {
            return this.processCheck(PNRef, amount, 0, transType);
        }

        public frontStreamReturn processCheck(string PNRef, string amount, long transId, string transType)
        {

            WebClient client = new WebClient();
            System.Collections.Specialized.NameValueCollection postVals = new System.Collections.Specialized.NameValueCollection();
            postVals.Add("username", serverSettings.Instance.frontstream_apiUsername);
            postVals.Add("password", serverSettings.Instance.frontstream_apiPassword);
            postVals.Add("transType", transType);

            postVals.Add("CheckNum", "");
            postVals.Add("TransitNum", "");
            postVals.Add("AccountNum", "");
            postVals.Add("amount", amount);
            postVals.Add("MICR", "");
            postVals.Add("NameOnCheck", "");
            postVals.Add("DL", "");
            postVals.Add("SS", "");
            postVals.Add("DOB", "");
            postVals.Add("StateCode", "");
            postVals.Add("CheckType", "");
            postVals.Add("ExtData", "<PNRef>" + PNRef + "</PNRef><InvNum>"+transId.ToString()+"</InvNum> ");

            string test = postVals.ToString();

            string uri = serverSettings.Instance.frontstream_apiEndpoint + "/ProcessCheck ";

            byte[] response = client.UploadValues(uri, "POST", postVals);

            frontStreamReturn returnObj = new frontStreamReturn();
            returnObj.rawXmlResponseString = Encoding.UTF8.GetString(response);

            return returnObj;
        }
    }
}
