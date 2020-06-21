using System;
using System.Collections.Generic;
using System.Windows.Forms;
using System.Drawing;
using System.ComponentModel;

namespace MailMergeAddIn
{
    class SingleContactDataMapListView : ListView
    {
        private ComboBox contactField;
        private ListViewItem selectedItem;
        private int mouseX;
        private int mouseY;

        public SingleContactDataMapListView()
        {
            initControl();
        }

        /**
         * remove
         */
        public SingleContactDataMapListView(String[] dataMapItems)
        {
            initControl();

            // TODO: as this is taken from a parameter, this will support merges from reports or from current workspace records
            contactField.Items.AddRange(dataMapItems); // add the drop down items
        }


        private List<string> contactFieldDataSource;
        private BindingList<string> bindingList;

        public List<string> ContactFieldDataSource
        {
            get
            {
                return contactFieldDataSource;
            }

            set
            {
                //if (value.IndexOf("") < 0)
                    value.Insert(0, ""); // add a blank index if one doesn't exist

                contactFieldDataSource = value;
                bindingList = new BindingList<string>(contactFieldDataSource);
                contactField.DataSource = bindingList;
            }
        }


        private SortedDictionary<string, string> contactFieldDataSourceDict;
        public SortedDictionary<string, string> ContactFieldDataSourceDict
        {
            get { return this.contactFieldDataSourceDict; }
            set
            {
                if (this.contactFieldDataSource.Count < 1)
                {
                    return;
                }
                this.contactFieldDataSourceDict = value;
                if (this.contactFieldDataSourceDict != null)
                {
                    this.contactFieldDataSourceDict.Add("", "");
                }
                contactField.DataSource = new BindingSource(this.contactFieldDataSourceDict, null);
                contactField.DisplayMember = "Key";
                contactField.ValueMember = "Value";
            }
        }

        private void initControl()
        {
            contactFieldDataSource = new List<string>();

            // add a combo box to the listview
            contactField = new ComboBox();
            contactField.Items.Clear();
            contactField.Hide();
            

            contactField.Size = new System.Drawing.Size(0, 0);
            contactField.Location = new System.Drawing.Point(0, 0);
            contactField.DropDownStyle = ComboBoxStyle.DropDownList;

            // add some events
            contactField.SelectedIndexChanged += new EventHandler(contactField_SelectedIndexChanged);
            contactField.LostFocus += new EventHandler(contactField_LostFocus);
            contactField.KeyPress += new KeyPressEventHandler(contactField_KeyPress);

            // add combox box to this control
            this.Controls.Add(contactField);

            // set the columns for this control
            this.Columns.Clear();
            this.Columns.Add("Merge Field", ((this.Width / 2) - 2), HorizontalAlignment.Left);
            this.Columns.Add("Contact Field", ((this.Width / 2) - 2), HorizontalAlignment.Left);

            // set some things by default
            this.View = View.Details;
            this.GridLines = true;
            this.Name = "singleContactDataMapListView";
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

                contactField.Size = new Size(width, height);
                contactField.Location = new Point(position, selectedItem.Bounds.Y);
                contactField.Show();
               //set the combobox to the previously selected value, or the 0th index if no value has been selected
                if (selectedItem.SubItems[selSubItem].Tag != null && selectedItem.SubItems[selSubItem].Tag is KeyValuePair<string, string>)
                {
                    KeyValuePair<string, string> selectedItemTag = (KeyValuePair<string, string>)selectedItem.SubItems[selSubItem].Tag;
                    contactField.Text = selectedItemTag.Key;
                }
                else
                {
                    contactField.SelectedIndex = 0;
                }
                contactField.SelectAll();
                contactField.Focus();
            }
        }

        public void changeRowRecordValue(string templateLookupField, string recordField)
        {
            int rowIndex = -1;
            foreach (ListViewItem item in this.Items)
            {
                if (item.Text == templateLookupField)
                {
                    rowIndex = item.Index;
                    break;
                }
            }
            if (rowIndex < 0)
            {
                return;
            }

            //get recordValue
            int contactFieldIdx = this.contactField.FindStringExact(recordField);
            if (contactFieldIdx > -1)
            {
                this.Items[rowIndex].SubItems[1].Text = ((KeyValuePair<string, string>)this.contactField.Items[contactFieldIdx]).Value;
                this.Items[rowIndex].SubItems[1].Tag = this.contactField.Items[contactFieldIdx];
            }
            //update the listview
            //this.Items[rowIndex].

        }

        void DataMapListView_MouseDown(object sender, MouseEventArgs e)
        {
            selectedItem = this.GetItemAt(e.X, e.Y);

            // save so we know where to draw the combo box
            mouseX = e.X;
            mouseY = e.Y;
        }

        void contactField_KeyPress(object sender, KeyPressEventArgs e)
        {
            // hide combox box if enter or esc is pressed
            if (e.KeyChar == 13 || e.KeyChar == 27)
                contactField.Hide();
        }

        void contactField_LostFocus(object sender, EventArgs e)
        {
            // hide the combox box if the control loses focus
            contactField.Hide();
        }

        void contactField_SelectedIndexChanged(object sender, EventArgs e)
        {
            if (contactField.Items.Count > 0 && selectedItem != null)
            {
                // get the current text value
                int subItemIndex = getSelectedSubItem();
                string oldValue = selectedItem.SubItems[subItemIndex].Text;
                string newValue = contactField.SelectedValue.ToString();
                
                // set the new value in the list view
                selectedItem.SubItems[subItemIndex].Text = newValue;
                selectedItem.SubItems[subItemIndex].Tag = contactField.SelectedItem;
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
