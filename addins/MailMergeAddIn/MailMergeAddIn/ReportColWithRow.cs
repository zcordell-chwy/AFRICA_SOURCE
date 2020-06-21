using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace MailMergeAddIn
{
    public class ReportColWithRow
    {
 
        public int RowId { get; set; }

        public Dictionary<string, string> fields { get; set; }
    }
}
