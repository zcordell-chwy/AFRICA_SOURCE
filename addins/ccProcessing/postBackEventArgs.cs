using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace ccProcessing
{
    public class postBackEventArgs: EventArgs
    {
        public string trackingId { get; private set; }
        public int transId { get; private set; }
        public string pnRef { get; private set; }
        public int statusCode { get; private set; }
        public string lastFour { get; private set; }
        public int authCode { get; private set; }
        public string cardType { get; private set; }
        public string expMonth { get; private set; }
        public string expYear { get; private set; }
        public Dictionary<string, string> getVals { get; private set; }
        private string _rawGetData;
        public string rawGetData
        {
            get { return this._rawGetData; }
            set{

                Dictionary<string, string> query = new Dictionary<string, string>();
                foreach (string item in value.Split("&".ToCharArray()))
                {
                    string attribute = System.Net.WebUtility.UrlDecode(item);
                    if (attribute.Length > 0)
                    {
                        query.Add(attribute.Substring(0, attribute.IndexOf("=")), attribute.Substring(attribute.IndexOf("=") + 1));
                    }
                }
                this.getVals = query;
                this._rawGetData = value;
                this.trackingId = query["TrackingID"];
                this.pnRef = query["PNRef"];
                this.statusCode = Convert.ToInt16(query["StatusCode"]);
                this.lastFour = query["AccountLastFour"];
                try
                {
                    this.authCode = Convert.ToInt32(query["AuthCode"]);
                }
                catch (Exception e)
                {
                    //auth code is string sometimes.  This is not currently used.
                }                
                this.cardType = query["CardType"];
                this.expMonth = query["AccountExpMonth"];
                this.expYear = query["AccountExpYear"];
            }
        }
    }
}
