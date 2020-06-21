using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Xml;


namespace ccProcessing
{
    public class frontStreamReturn
    {
        public int result { get; private set; }
        public string RespMSG { get; private set; }
        public string Message { get; private set; }
        public string Message1 { get; private set; }
        public string Message2 { get; private set; }
        public string AuthCode { get; private set; }
        public string ReceiptURL { get; private set; }

        public XmlDocument rawXml { get; private set; }

        private string _rawXmlStr;
        public string rawXmlResponseString
        {
            get { return _rawXmlStr; }
            set
            {
                _rawXmlStr = value;
                XmlDocument doc = new XmlDocument();
                doc.LoadXml(value);
                this.rawXml = doc;

                this.RespMSG = doc.GetElementsByTagName("RespMSG").Count > 0 ? doc.GetElementsByTagName("RespMSG").Item(0).InnerText : null;
                this.result = doc.GetElementsByTagName("Result").Count > 0 ? int.Parse(doc.GetElementsByTagName("Result").Item(0).InnerText) : -1;
                this.Message = doc.GetElementsByTagName("Message").Count > 0 ? doc.GetElementsByTagName("Message").Item(0).InnerText : null;
                this.Message1 = doc.GetElementsByTagName("Message1").Count > 0 ? doc.GetElementsByTagName("Message1").Item(0).InnerText : null;
                this.Message2 = doc.GetElementsByTagName("Message2").Count > 0 ? doc.GetElementsByTagName("Message2").Item(0).InnerText : null;
                this.AuthCode = doc.GetElementsByTagName("AuthCode").Count > 0 ? doc.GetElementsByTagName("AuthCode").Item(0).InnerText : null;
                this.ReceiptURL = doc.GetElementsByTagName("ReceiptURL").Count > 0 ? doc.GetElementsByTagName("ReceiptURL").Item(0).InnerText : null;
            }
        }
    }
}
