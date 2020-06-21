using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using ccProcessing.cws;

namespace ccProcessing
{ 
    public class paymentMethods
    {
        public List<GenericObject> genericPaymentMethods
        {           
            set
            {
                this.typedPaymentMethods = new List<paymentMethod>();
                foreach (GenericObject method in value)
                {
                    paymentMethod newMethod = new paymentMethod();
                    GenericField cardType = method.GenericFields.Where(field => field.name == "CardType" && field.DataValue != null).FirstOrDefault();
                    if (cardType != null)
                    {
                        newMethod.cardType = cardType.DataValue.Items[0].ToString();
                    }
                    GenericField PN_Ref = method.GenericFields.Where(field => field.name == "PN_Ref" && field.DataValue != null).FirstOrDefault();
                    if (PN_Ref != null)
                    {
                        newMethod.pnRef = PN_Ref.DataValue.Items[0].ToString();
                    }
                    GenericField expMonth = method.GenericFields.Where(field => field.name == "expMonth" && field.DataValue != null).FirstOrDefault();
                    if (expMonth != null)
                    {
                        newMethod.expMonth = expMonth.DataValue.Items[0].ToString();
                    }
                    GenericField expYear = method.GenericFields.Where(field => field.name == "expYear" && field.DataValue != null).FirstOrDefault();
                    if (expYear != null)
                    {
                        newMethod.expYear = expYear.DataValue.Items[0].ToString();
                    }
                    GenericField lastFour = method.GenericFields.Where(field => field.name == "lastFour" && field.DataValue != null).FirstOrDefault();
                    if (lastFour != null)
                    {
                        newMethod.lastFour = lastFour.DataValue.Items[0].ToString();
                    }
                    this.typedPaymentMethods.Add(newMethod);
                }
            }
        }

        public List<paymentMethod> typedPaymentMethods
        {
            get;
            private set;
        }

    }
}
