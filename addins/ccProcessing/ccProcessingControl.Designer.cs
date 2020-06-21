namespace ccProcessing
{
    partial class ccProcessingControl
    {
        /// <summary> 
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary> 
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            this.frontStreamDisplay.Navigated -= handler;
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Component Designer generated code

        /// <summary> 
        /// Required method for Designer support - do not modify 
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.components = new System.ComponentModel.Container();
            this.paymentMethodsGrid = new System.Windows.Forms.DataGridView();
            this.chargeCard = new System.Windows.Forms.DataGridViewButtonColumn();
            this.lastFourDataGridViewTextBoxColumn = new System.Windows.Forms.DataGridViewTextBoxColumn();
            this.expMonthDataGridViewTextBoxColumn = new System.Windows.Forms.DataGridViewTextBoxColumn();
            this.expYearDataGridViewTextBoxColumn = new System.Windows.Forms.DataGridViewTextBoxColumn();
            this.cardTypeDataGridViewTextBoxColumn = new System.Windows.Forms.DataGridViewTextBoxColumn();
            this.pnRefDataGridViewTextBoxColumn = new System.Windows.Forms.DataGridViewTextBoxColumn();
            this.idDataGridViewTextBoxColumn = new System.Windows.Forms.DataGridViewTextBoxColumn();
            this.paymentMethodsBindingSource = new System.Windows.Forms.BindingSource(this.components);
            this.addMethodBtn = new System.Windows.Forms.Button();
            this.RefundBtn = new System.Windows.Forms.Button();
            this.ChargeNewMethodBtn = new System.Windows.Forms.Button();
            this.frontStreamDisplay = new System.Windows.Forms.WebBrowser();
            this.tableLayoutPanel1 = new System.Windows.Forms.TableLayoutPanel();
            this.wsNotReadyLbl = new System.Windows.Forms.Label();
            ((System.ComponentModel.ISupportInitialize)(this.paymentMethodsGrid)).BeginInit();
            ((System.ComponentModel.ISupportInitialize)(this.paymentMethodsBindingSource)).BeginInit();
            this.tableLayoutPanel1.SuspendLayout();
            this.SuspendLayout();
            // 
            // paymentMethodsGrid
            // 
            this.paymentMethodsGrid.AllowUserToAddRows = false;
            this.paymentMethodsGrid.AllowUserToDeleteRows = false;
            this.paymentMethodsGrid.AutoGenerateColumns = false;
            this.paymentMethodsGrid.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.paymentMethodsGrid.Columns.AddRange(new System.Windows.Forms.DataGridViewColumn[] {
            this.chargeCard,
            this.lastFourDataGridViewTextBoxColumn,
            this.expMonthDataGridViewTextBoxColumn,
            this.expYearDataGridViewTextBoxColumn,
            this.cardTypeDataGridViewTextBoxColumn,
            this.pnRefDataGridViewTextBoxColumn,
            this.idDataGridViewTextBoxColumn});
            this.tableLayoutPanel1.SetColumnSpan(this.paymentMethodsGrid, 3);
            this.paymentMethodsGrid.DataBindings.Add(new System.Windows.Forms.Binding("Tag", this.paymentMethodsBindingSource, "pnRef", true));
            this.paymentMethodsGrid.DataSource = this.paymentMethodsBindingSource;
            this.paymentMethodsGrid.Location = new System.Drawing.Point(3, 32);
            this.paymentMethodsGrid.Name = "paymentMethodsGrid";
            this.paymentMethodsGrid.ReadOnly = true;
            this.paymentMethodsGrid.Size = new System.Drawing.Size(695, 198);
            this.paymentMethodsGrid.TabIndex = 1;
            this.paymentMethodsGrid.CellClick += new System.Windows.Forms.DataGridViewCellEventHandler(this.paymentMethodsGrid_CellClick);
            // 
            // chargeCard
            // 
            this.chargeCard.AutoSizeMode = System.Windows.Forms.DataGridViewAutoSizeColumnMode.Fill;
            this.chargeCard.HeaderText = "Charge Card";
            this.chargeCard.Name = "chargeCard";
            this.chargeCard.ReadOnly = true;
            this.chargeCard.Text = "Make Payment";
            this.chargeCard.ToolTipText = "Make payment using stored payment method.";
            this.chargeCard.UseColumnTextForButtonValue = true;
            // 
            // lastFourDataGridViewTextBoxColumn
            // 
            this.lastFourDataGridViewTextBoxColumn.AutoSizeMode = System.Windows.Forms.DataGridViewAutoSizeColumnMode.Fill;
            this.lastFourDataGridViewTextBoxColumn.DataPropertyName = "lastFour";
            this.lastFourDataGridViewTextBoxColumn.HeaderText = "Last Four";
            this.lastFourDataGridViewTextBoxColumn.Name = "lastFourDataGridViewTextBoxColumn";
            this.lastFourDataGridViewTextBoxColumn.ReadOnly = true;
            // 
            // expMonthDataGridViewTextBoxColumn
            // 
            this.expMonthDataGridViewTextBoxColumn.AutoSizeMode = System.Windows.Forms.DataGridViewAutoSizeColumnMode.Fill;
            this.expMonthDataGridViewTextBoxColumn.DataPropertyName = "expMonth";
            this.expMonthDataGridViewTextBoxColumn.HeaderText = "Exp. Month";
            this.expMonthDataGridViewTextBoxColumn.Name = "expMonthDataGridViewTextBoxColumn";
            this.expMonthDataGridViewTextBoxColumn.ReadOnly = true;
            // 
            // expYearDataGridViewTextBoxColumn
            // 
            this.expYearDataGridViewTextBoxColumn.AutoSizeMode = System.Windows.Forms.DataGridViewAutoSizeColumnMode.Fill;
            this.expYearDataGridViewTextBoxColumn.DataPropertyName = "expYear";
            this.expYearDataGridViewTextBoxColumn.HeaderText = "Exp. Year";
            this.expYearDataGridViewTextBoxColumn.Name = "expYearDataGridViewTextBoxColumn";
            this.expYearDataGridViewTextBoxColumn.ReadOnly = true;
            // 
            // cardTypeDataGridViewTextBoxColumn
            // 
            this.cardTypeDataGridViewTextBoxColumn.AutoSizeMode = System.Windows.Forms.DataGridViewAutoSizeColumnMode.Fill;
            this.cardTypeDataGridViewTextBoxColumn.DataPropertyName = "cardType";
            this.cardTypeDataGridViewTextBoxColumn.HeaderText = "Card Type";
            this.cardTypeDataGridViewTextBoxColumn.Name = "cardTypeDataGridViewTextBoxColumn";
            this.cardTypeDataGridViewTextBoxColumn.ReadOnly = true;
            // 
            // pnRefDataGridViewTextBoxColumn
            // 
            this.pnRefDataGridViewTextBoxColumn.AutoSizeMode = System.Windows.Forms.DataGridViewAutoSizeColumnMode.Fill;
            this.pnRefDataGridViewTextBoxColumn.DataPropertyName = "pnRef";
            this.pnRefDataGridViewTextBoxColumn.HeaderText = "PN Ref";
            this.pnRefDataGridViewTextBoxColumn.Name = "pnRefDataGridViewTextBoxColumn";
            this.pnRefDataGridViewTextBoxColumn.ReadOnly = true;
            // 
            // idDataGridViewTextBoxColumn
            // 
            this.idDataGridViewTextBoxColumn.DataPropertyName = "id";
            this.idDataGridViewTextBoxColumn.HeaderText = "ID";
            this.idDataGridViewTextBoxColumn.Name = "idDataGridViewTextBoxColumn";
            this.idDataGridViewTextBoxColumn.ReadOnly = true;
            // 
            // paymentMethodsBindingSource
            // 
            this.paymentMethodsBindingSource.DataMember = "typedPaymentMethods";
            this.paymentMethodsBindingSource.DataSource = typeof(ccProcessing.paymentMethods);
            // 
            // addMethodBtn
            // 
            this.addMethodBtn.AutoSize = true;
            this.addMethodBtn.AutoSizeMode = System.Windows.Forms.AutoSizeMode.GrowAndShrink;
            this.addMethodBtn.Location = new System.Drawing.Point(3, 3);
            this.addMethodBtn.Name = "addMethodBtn";
            this.addMethodBtn.Size = new System.Drawing.Size(144, 23);
            this.addMethodBtn.TabIndex = 4;
            this.addMethodBtn.Text = "Add New Payment Method";
            this.addMethodBtn.UseVisualStyleBackColor = true;
            this.addMethodBtn.Click += new System.EventHandler(this.addMethodBtn_Click);
            // 
            // RefundBtn
            // 
            this.RefundBtn.AutoSize = true;
            this.RefundBtn.AutoSizeMode = System.Windows.Forms.AutoSizeMode.GrowAndShrink;
            this.RefundBtn.Location = new System.Drawing.Point(153, 3);
            this.RefundBtn.Name = "RefundBtn";
            this.RefundBtn.Size = new System.Drawing.Size(98, 23);
            this.RefundBtn.TabIndex = 2;
            this.RefundBtn.Text = "Refund Donation";
            this.RefundBtn.UseVisualStyleBackColor = true;
            this.RefundBtn.Click += new System.EventHandler(this.RefundBtn_Click);
            // 
            // ChargeNewMethodBtn
            // 
            this.ChargeNewMethodBtn.AutoSizeMode = System.Windows.Forms.AutoSizeMode.GrowAndShrink;
            this.ChargeNewMethodBtn.Location = new System.Drawing.Point(257, 3);
            this.ChargeNewMethodBtn.Name = "ChargeNewMethodBtn";
            this.ChargeNewMethodBtn.Size = new System.Drawing.Size(177, 23);
            this.ChargeNewMethodBtn.TabIndex = 3;
            this.ChargeNewMethodBtn.Text = "Charge New Payment Method";
            this.ChargeNewMethodBtn.UseVisualStyleBackColor = true;
            this.ChargeNewMethodBtn.Click += new System.EventHandler(this.chargeNewMethodBtn_Click);
            // 
            // frontStreamDisplay
            // 
            this.tableLayoutPanel1.SetColumnSpan(this.frontStreamDisplay, 3);
            this.frontStreamDisplay.Location = new System.Drawing.Point(3, 236);
            this.frontStreamDisplay.MinimumSize = new System.Drawing.Size(20, 20);
            this.frontStreamDisplay.Name = "frontStreamDisplay";
            this.frontStreamDisplay.Size = new System.Drawing.Size(695, 693);
            this.frontStreamDisplay.TabIndex = 0;
            this.frontStreamDisplay.Url = new System.Uri("about:blank", System.UriKind.Absolute);
            this.frontStreamDisplay.Visible = false;
            // 
            // tableLayoutPanel1
            // 
            this.tableLayoutPanel1.ColumnCount = 3;
            this.tableLayoutPanel1.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle());
            this.tableLayoutPanel1.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle());
            this.tableLayoutPanel1.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle());
            this.tableLayoutPanel1.Controls.Add(this.ChargeNewMethodBtn, 2, 0);
            this.tableLayoutPanel1.Controls.Add(this.addMethodBtn, 0, 0);
            this.tableLayoutPanel1.Controls.Add(this.frontStreamDisplay, 0, 2);
            this.tableLayoutPanel1.Controls.Add(this.RefundBtn, 1, 0);
            this.tableLayoutPanel1.Controls.Add(this.paymentMethodsGrid, 1, 1);
            this.tableLayoutPanel1.Location = new System.Drawing.Point(3, 3);
            this.tableLayoutPanel1.Name = "tableLayoutPanel1";
            this.tableLayoutPanel1.RowCount = 3;
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle());
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Absolute, 20F));
            this.tableLayoutPanel1.Size = new System.Drawing.Size(698, 947);
            this.tableLayoutPanel1.TabIndex = 1;
            this.tableLayoutPanel1.Visible = false;
            // 
            // wsNotReadyLbl
            // 
            this.wsNotReadyLbl.AutoSize = true;
            this.wsNotReadyLbl.Location = new System.Drawing.Point(0, 0);
            this.wsNotReadyLbl.Name = "wsNotReadyLbl";
            this.wsNotReadyLbl.Size = new System.Drawing.Size(215, 13);
            this.wsNotReadyLbl.TabIndex = 2;
            this.wsNotReadyLbl.Text = "Workspace must be saved prior to payment.";
            // 
            // ccProcessingControl
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.Controls.Add(this.wsNotReadyLbl);
            this.Controls.Add(this.tableLayoutPanel1);
            this.Name = "ccProcessingControl";
            this.Size = new System.Drawing.Size(918, 953);
            ((System.ComponentModel.ISupportInitialize)(this.paymentMethodsGrid)).EndInit();
            ((System.ComponentModel.ISupportInitialize)(this.paymentMethodsBindingSource)).EndInit();
            this.tableLayoutPanel1.ResumeLayout(false);
            this.tableLayoutPanel1.PerformLayout();
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.WebBrowser frontStreamDisplay;
        private System.Windows.Forms.DataGridView paymentMethodsGrid;
        private System.Windows.Forms.BindingSource paymentMethodsBindingSource;
        private System.Windows.Forms.Button RefundBtn;
        private System.Windows.Forms.Button ChargeNewMethodBtn;
        private System.Windows.Forms.Button addMethodBtn;
        private System.Windows.Forms.TableLayoutPanel tableLayoutPanel1;
        private System.Windows.Forms.DataGridViewButtonColumn chargeCard;
        private System.Windows.Forms.DataGridViewTextBoxColumn lastFourDataGridViewTextBoxColumn;
        private System.Windows.Forms.DataGridViewTextBoxColumn expMonthDataGridViewTextBoxColumn;
        private System.Windows.Forms.DataGridViewTextBoxColumn expYearDataGridViewTextBoxColumn;
        private System.Windows.Forms.DataGridViewTextBoxColumn cardTypeDataGridViewTextBoxColumn;
        private System.Windows.Forms.DataGridViewTextBoxColumn pnRefDataGridViewTextBoxColumn;
        private System.Windows.Forms.DataGridViewTextBoxColumn idDataGridViewTextBoxColumn;
        private System.Windows.Forms.Label wsNotReadyLbl;
    }
}
