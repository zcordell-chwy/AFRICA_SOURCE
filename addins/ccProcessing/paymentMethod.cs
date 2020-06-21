using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace ccProcessing
{
    public class paymentMethod
    {
        public string lastFour { get; set; }
        public string expMonth  { get; set; }
        public string expYear { get; set; }
        public string cardType { get; set; }
        public string pnRef { get; set; }
        public string id { get; set; }
    }
}
