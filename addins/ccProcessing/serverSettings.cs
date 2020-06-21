using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace ccProcessing
{
    class serverSettings
    {

        private serverSettings() { }
        private static readonly Lazy<serverSettings> _instance = new Lazy<serverSettings>(() => new serverSettings());
        public static serverSettings Instance { get { return _instance.Value; } }
        
        public string actionName_initiateRefund { get; set; }
        public string actionName_addPaymentMethod { get; set; }
        public string actionName_makePayment { get; set; }

        public string frontstreamHosted_makePayment { get; set; }
        
        public string frontstreamHosted_userToken { get; set; }
        public string frontstreamHosted_username{ get; set; }
        public string frontstreamHosted_merchantKey { get; set; }
        public string frontstreamHosted_merchantToken { get; set; }
        public string frontstreamHosted_postbackUrl { get; set; }
        public string frontstreamHosted_headerUrl { get; set; }
        public string frontstream_apiUsername { get; set; }
        public string frontstream_apiPassword { get; set; }
        public string frontstream_apiEndpoint { get; set; }




        
    }
}
