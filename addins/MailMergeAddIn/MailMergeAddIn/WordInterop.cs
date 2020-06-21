using System;
using System.Runtime.InteropServices;
using Microsoft.Office.Interop.Word;

namespace MailMergeAddIn
{
    class WordInterop
    {
        private static object varMissing = Type.Missing;
        private static object varTrue = true;
        private static object varFalse = false;

        public static void sendToPrinter(string fileName)
        {
            try
            {
                object varFileName = fileName;

                ApplicationClass wordApp = new ApplicationClass();
                wordApp.Visible = false;
                wordApp.ShowWindowsInTaskbar = false;

                Document doc = wordApp.Documents.Open(ref varFileName, ref varMissing, ref varTrue, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing);

                doc.PrintOut(ref varTrue, ref varFalse, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing,
                    ref varMissing, ref varFalse, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing);

                doc.Close(ref varFalse, ref varMissing, ref varMissing);
                wordApp.Quit(ref varMissing, ref varMissing, ref varMissing);
            }
            catch (Exception ex) 
            {
 
            }
        }

        public static void openDocument(string fileName)
        {
            try
            {
                object varFileName = fileName;

                ApplicationClass wordApp = new ApplicationClass();
                wordApp.Visible = true;
                wordApp.ShowWindowsInTaskbar = true;

                Document doc = wordApp.Documents.Open(ref varFileName, ref varMissing, ref varTrue, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing,
                    ref varMissing, ref varMissing, ref varMissing);
            }
            catch { }
        }

    }
}
