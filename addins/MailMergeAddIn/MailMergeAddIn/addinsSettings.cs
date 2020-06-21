using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace MailMergeAddIn
{
    public class addinsSettings
    {
        private addinsSettings() { }
        private static readonly Lazy<addinsSettings> _instance = new Lazy<addinsSettings>(() => new addinsSettings());
        public static addinsSettings Instance { get { return _instance.Value; } }

        public string defaultTemplDir { get; set; }
        public string templateCustomField { get; set; }
    }
}
