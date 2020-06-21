using System;
using System.IO;
using System.Xml;
using System.Text;
using System.Linq;
using System.Collections.Generic;
using System.Diagnostics;



using com.rightnow.MailMerge.WebService;

using Microsoft.Office.Interop.Word;
using System.Text.RegularExpressions;

namespace MailMergeAddIn
{
    public class MailMergeDocument
    {
        private static string Namespace { get { return "http://schemas.openxmlformats.org/wordprocessingml/2006/main"; } }

        public static List<string> getMergeFields(string fileName)
        {
            try
            {
                List<string> mergeFlds = new List<string>();

                if(fileName.Contains(".dotx")){
                    WordprocessingDocument doc = WordprocessingDocument.Open(fileName, false); // open in read-only
                    XmlDocument xmlDoc = new XmlDocument();
                    xmlDoc.Load(doc.MainDocumentPart.GetStream());

                    NameTable nt = new NameTable();
                    XmlNamespaceManager nsManager = new XmlNamespaceManager(nt);
                    nsManager.AddNamespace("w", MailMergeDocument.Namespace);
                    XmlNodeList nodes = xmlDoc.SelectNodes("//w:t[starts-with(text(),'«')]", nsManager);

                   
                    foreach (XmlNode node in nodes)
                    {
                        // only return a unique list of merge fields
                        if (mergeFlds.IndexOf(node.InnerText) == -1)
                            mergeFlds.Add(node.InnerText);
                    }
                }
                else//for html, htm files
                {
                    string fileHtml = System.IO.File.ReadAllText(fileName);
                    Regex ItemRegex = new Regex(@"&lt;&lt;(.*?)&gt;&gt;");

                    foreach (Match match in ItemRegex.Matches(fileHtml))
                    {
                        mergeFlds.Add(match.Value);

                    }
                    

                }

                return mergeFlds;
            }
            catch
            {
                System.Windows.Forms.MessageBox.Show("Error: Could not open template or read merge fields.  Please close the template, close Word, and try again.");
            }

            return new List<string>();
        }

        public static void mergeDocument(string tmplFile, string outFile, DataMapItem[] dmItems)
        {
            try
            {
                // copy the tmplFile to the outFile
                File.Copy(tmplFile, outFile, true); // overwrite the file if it already exists
                // open the outFile and change the document type
                WordprocessingDocument doc = WordprocessingDocument.Open(outFile, true); // open in rw mode
                doc.ChangeDocumentType(WordprocessingDocumentType.Document);
                XmlDocument xmlDoc = new XmlDocument();
                xmlDoc.Load(doc.MainDocumentPart.GetStream());
                NameTable nt = new NameTable();
                XmlNamespaceManager nsManager = new XmlNamespaceManager(nt);
                nsManager.AddNamespace("w", MailMergeDocument.Namespace);
                XmlNodeList nodes = xmlDoc.SelectNodes("//w:t[starts-with(text(),'«')]", nsManager);
                // perform the merge
                foreach (XmlNode node in nodes)
                {
                    // get the merge fields new value from the data map
                    foreach (DataMapItem dmItem in dmItems)
                    {
                        if(dmItem != null && dmItem.TmplFld != null)
                        {
                            if (node.FirstChild.InnerText == dmItem.TmplFld)
                            {
                                // replace the inner text of the node
                                node.FirstChild.InnerText = dmItem.Value;
                            }
                        }
                       
                    }
                }
                // save and close the file
                Stream docStream =  doc.MainDocumentPart.GetStream();

                xmlDoc.Save(docStream); 
                doc.Close();
            }
            catch { }
        }

        //merge field and pop open a browser to view the html content
        public static void mergeHtmlDocument(string tmplFile, string outFile, DataMapItem[] dmItems)
        {
            try
            {

                string fileHtml = System.IO.File.ReadAllText(tmplFile);

                foreach (DataMapItem dmItem in dmItems){
                   if(dmItem != null && dmItem.TmplFld != null){
                        fileHtml = fileHtml.Replace(dmItem.TmplFld, dmItem.Value);
                    }      
                }

                string filename = string.Format(@"{0}\{1}",System.IO.Path.GetTempPath(), "testhtm.htm");
                File.WriteAllText(filename, fileHtml);
                Process.Start(filename);
                
            }
            catch { }
        }

        public static void combineDocuments(string origFile, string fileToAdd, string altChunkId)
        {
            using (WordprocessingDocument doc = WordprocessingDocument.Open(origFile, true))
            {
                MainDocumentPart part = doc.MainDocumentPart;

                AlternativeFormatImportPart chunk = part.AddAlternativeFormatImportPart(
                    AlternativeFormatImportPartType.WordprocessingML, altChunkId);
                using (FileStream fs = File.Open(fileToAdd, FileMode.Open))
                    chunk.FeedData(fs);

                AltChunk altChunk = new AltChunk();
                altChunk.Id = altChunkId;

                // create a new page break first...
                DocumentFormat.OpenXml.Wordprocessing.Paragraph para = new DocumentFormat.OpenXml.Wordprocessing.Paragraph(new Run(new DocumentFormat.OpenXml.Wordprocessing.Break() { Type = BreakValues.Page }));
                part.Document.Body.InsertAfter(para, part.Document.Body.LastChild);

                // now insert the other document
                part.Document.Body.InsertAfter(altChunk, part.Document.Body.LastChild);
                part.Document.Save();
            }
        }

        public static void convertToPdf(string origFile)
        {
            // It looks like it fails when the filename is just .docx.  So change it to tmp.docx in this special case.
            bool specialCase = false;

            if (origFile.EndsWith("\\.docx"))
            {
                specialCase = true;
                File.Copy(origFile, Regex.Replace(origFile, "\\.docx", "\\tmp.docx"), true);
                origFile = Regex.Replace(origFile, "\\.docx", "\\tmp.docx");
            }
            
            ApplicationClass wordApplication = new ApplicationClass();
            Microsoft.Office.Interop.Word.Document wordDocument = null;
            object paramSourceDocPath = @origFile;
            object paramMissing = Type.Missing;
            string paramExportFilePath = @Regex.Replace(origFile, "\\.docx$", ".pdf");
            WdExportFormat paramExportFormat = WdExportFormat.wdExportFormatPDF;
            bool paramOpenAfterExport = false;
            WdExportOptimizeFor paramExportOptimizeFor = WdExportOptimizeFor.wdExportOptimizeForPrint;
            WdExportRange paramExportRange = WdExportRange.wdExportAllDocument;
            int paramStartPage = 0;
            int paramEndPage = 0;
            WdExportItem paramExportItem = WdExportItem.wdExportDocumentContent;
            bool paramIncludeDocProps = true;
            bool paramKeepIRM = true;
            WdExportCreateBookmarks paramCreateBookmarks = WdExportCreateBookmarks.wdExportCreateWordBookmarks;
            bool paramDocStructureTags = true;
            bool paramBitmapMissingFonts = true;
            bool paramUseISO19005_1 = false;
            
            try
            {
                wordDocument = wordApplication.Documents.Open(
                    ref paramSourceDocPath, ref paramMissing, ref paramMissing,
                    ref paramMissing, ref paramMissing, ref paramMissing,
                    ref paramMissing, ref paramMissing, ref paramMissing,
                    ref paramMissing, ref paramMissing, ref paramMissing,
                    ref paramMissing, ref paramMissing, ref paramMissing,
                    ref paramMissing);
                // Export it in the specified format.
                if (wordDocument != null)
                    wordDocument.ExportAsFixedFormat(paramExportFilePath,
                        paramExportFormat, paramOpenAfterExport,
                        paramExportOptimizeFor, paramExportRange, paramStartPage,
                        paramEndPage, paramExportItem, paramIncludeDocProps,
                        paramKeepIRM, paramCreateBookmarks, paramDocStructureTags,
                        paramBitmapMissingFonts, paramUseISO19005_1,
                        ref paramMissing);

            }
            catch (Exception ex)
            {
                System.Windows.Forms.MessageBox.Show("Error: " + ex.Message, "PDF Export Error");
            }
            finally
            {
                // Close and release the Document object.
                if (wordDocument != null)
                {
                    wordDocument.Close(ref paramMissing, ref paramMissing,
                        ref paramMissing);
                    wordDocument = null;
                }

                // Quit Word and release the ApplicationClass object.
                if (wordApplication != null)
                {
                    wordApplication.Quit(ref paramMissing, ref paramMissing,
                        ref paramMissing);
                    wordApplication = null;
                }

                GC.Collect();
                GC.WaitForPendingFinalizers();
                GC.Collect();
                GC.WaitForPendingFinalizers();
            }
            if (specialCase)
            {
                File.Copy(origFile, Regex.Replace(origFile, "\\\\tmp\\.docx", "\\.docx"), true);
                String newFile = Regex.Replace(origFile, "\\\\tmp\\.docx", "\\tmp.pdf");
                File.Copy(newFile, Regex.Replace(newFile, "\\\\tmp\\.pdf", "\\.pdf"), true);
            }
        }
    }    
}
