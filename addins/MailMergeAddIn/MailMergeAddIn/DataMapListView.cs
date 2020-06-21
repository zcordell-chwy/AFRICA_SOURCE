using System;
using System.Collections.Generic;
using System.Windows.Forms;
using System.Drawing;
using System.ComponentModel;

namespace MailMergeAddIn
{
    class DataMapListView : ListView
    {
        private ComboBox reportField;
        private ListViewItem selectedItem;
        private int mouseX;
        private int mouseY;

        public DataMapListView()
        {
            initControl();
        }

        public DataMapListView(String[] dataMapItems)
        {
            initControl();

            // TODO: as this is taken from a parameter, this will support merges from reports or from current workspace records
            reportField.Items.AddRange(dataMapItems); // add the drop down items
        }

        private List<string> reportFieldDataSource;
        private BindingList<string> bindingList;

        public List<string> ReportFieldDataSource
        {
            get
            {
                return reportFieldDataSource;
            }

            set
            {
                if (value.IndexOf("") < 0)
                    value.Insert(0, ""); // add a blank index if one doesn't exist

                reportFieldDataSource = value;
                bindingList = new BindingList<string>(reportFieldDataSource);
                reportField.DataSource = bindingList;
            }
        }

        private void initControl()
        {
            reportFieldDataSource = new List<string>();

            // add a combo box to the listview
            reportField = new ComboBox();
            reportField.Items.Clear();
            reportField.Hide();

            reportField.Size = new System.Drawing.Size(0, 0);
            reportField.Location = new System.Drawing.Point(0, 0);
            reportField.DropDownStyle = ComboBoxStyle.DropDownList;

            // add some events
            reportField.SelectedIndexChanged += new EventHandler(reportField_SelectedIndexChanged);
            reportField.LostFocus += new EventHandler(reportField_LostFocus);
            reportField.KeyPress += new KeyPressEventHandler(reportField_KeyPress);

            // add combox box to this control
            this.Controls.Add(reportField);

            // set the columns for this control
            this.Columns.Clear();
            this.Columns.Add("Merge Field", ((this.Width / 2) - 2), HorizontalAlignment.Left);
            this.Columns.Add("Report Column", ((this.Width / 2) - 2), HorizontalAlignment.Left);

            // set some things by default
            this.View = View.Details;
            this.GridLines = true;
            this.Name = "dataMapListView";
            this.FullRowSelect = true;

            // subscribe to events
            this.MouseDown += new MouseEventHandler(DataMapListView_MouseDown);
            this.DoubleClick += new EventHandler(DataMapListView_DoubleClick);
            this.ClientSizeChanged += new EventHandler(DataMapListView_ClientSizeChanged);
        }

        void DataMapListView_ClientSizeChanged(object sender, EventArgs e)
        {
            for (int i = 0; i < this.Columns.Count; ++i)
            {
                this.Columns[i].Width = (int)(this.ClientSize.Width / this.Columns.Count);
            }
        }

        private int getSelectedSubItem()
        {
            int start = mouseX;
            int position = 0;
            int end = this.Columns[0].Width;
            int selSubItem = 0;

            for (int i = 0; i < this.Columns.Count; ++i)
            {
                if (start > position && start < end)
                {
                    selSubItem = i;
                    break;
                }

                position = end;
                end += this.Columns[i].Width;
            }

            return selSubItem;
        }

        void DataMapListView_DoubleClick(object sender, EventArgs e)
        {
            // fire resize in case there are scrollbars
            // this.DataMapListView_Resize(null, new EventArgs());

            // show the combox box if we're in the second column
            int start = mouseX;
            int position = 0;
            int end = this.Columns[0].Width;
            int selSubItem = 0;

            for (int i = 0; i < this.Columns.Count; ++i)
            {
                if (start > position && start < end)
                {
                    selSubItem = i;
                    break;
                }

                position = end;
                end += this.Columns[i].Width;
            }

            // if this event is being fired from the second column...
            if (selSubItem == 1)
            {
                int width = end - position;
                int height = selectedItem.Bounds.Bottom - selectedItem.Bounds.Top;

                reportField.Size = new Size(width, height);
                reportField.Location = new Point(position, selectedItem.Bounds.Y);
                reportField.Show();
                reportField.Text = selectedItem.SubItems[selSubItem].Text;
                reportField.SelectAll();
                reportField.Focus();
            }
        }

        void DataMapListView_MouseDown(object sender, MouseEventArgs e)
        {
            selectedItem = this.GetItemAt(e.X, e.Y);

            // save so we know where to draw the combo box
            mouseX = e.X;
            mouseY = e.Y;
        }

        void reportField_KeyPress(object sender, KeyPressEventArgs e)
        {
            // hide combox box if enter or esc is pressed
            if (e.KeyChar == 13 || e.KeyChar == 27)
                reportField.Hide();
        }

        void reportField_LostFocus(object sender, EventArgs e)
        {
            // hide the combox box if the control loses focus
            reportField.Hide();
        }

        void reportField_SelectedIndexChanged(object sender, EventArgs e)
        {
            if (reportField.Items.Count > 0 && selectedItem != null)
            {
                // get the current text value
                int subItemIndex = getSelectedSubItem();
                string oldValue = selectedItem.SubItems[subItemIndex].Text;
                string newValue = reportField.Items[reportField.SelectedIndex].ToString();

                // set the new value in the list view
                selectedItem.SubItems[subItemIndex].Text = newValue;
            }
        }

        public bool isDataMapValid()
        {
            for (int i = 0; i < this.Items.Count; ++i)
            {
                // we have each row...
                for (int x = 0; x < this.Items[i].SubItems.Count; ++x)
                {
                    // check to ensure both columns have a value
                    // NOTE: there should not be any rows/columns without a value
                    if (this.Items[i].SubItems[x].Text.Length == 0)
                    {
                        return false;
                    }
                }
            }

            return true;
        }
    }
}
