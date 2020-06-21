using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using ccProcessing.cws;
namespace ccProcessing
{
    public class aggregateTransactionData
    {
        public string pnRef { get; private set; }
        public string amount { get; private set; }
        public string receiptPNRef { get; private set; }
        public long paymentMethodId { get; private set; }
        public long transId { get; private set; }
        private GenericObject _transObj;
        public GenericObject transObj
        {
            get { return this._transObj; }
            set
            {
                GenericField amount = value.GenericFields.Where(field => field.name.Equals("totalCharge")).First() as GenericField;
                if (amount != null && amount.DataValue.Items.Count() > 0)
                {
                    this.amount = amount.DataValue.Items[0].ToString();
                }
                this.transId = value.ID.id;
                GenericField _receiptPNRef = value.GenericFields.Where(field => field.name.Equals("refCode")).First() as GenericField;
                if (_receiptPNRef != null && _receiptPNRef.DataValue != null)
                {
                    if (_receiptPNRef.DataValue.Items.Count() > 0)
                    {
                        this.receiptPNRef = _receiptPNRef.DataValue.Items[0].ToString();
                    }
                }
 

                this._transObj = value;
            }
        }
        private paymentMethod _paymentMethod;
        public paymentMethod paymentMethod
        {
            get { return _paymentMethod; }
            set
            {
                this.pnRef = value.pnRef;
                this.paymentMethodId = long.Parse( value.id );
                this._paymentMethod = value;

            }
        }
    }
}
