export type ApiSuccess<T> = {
  success: true;
  message: string;
  data: T;
};

export type ApiError = {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
};

export type ApiPaginated<T> = {
  success: true;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
};

export type BusinessType = "generic" | "referee";

export type Tenant = {
  id: number;
  name: string;
  slug: string;
  business_type?: BusinessType;
  email: string | null;
  status: string;
  trial_ends_at: string | null;
  plan?: Plan | null;
  current_subscription?: Subscription | null;
};

export type Plan = {
  id: number;
  name: string;
  price: number;
  priceMonthly: number;
  priceYearly: number;
  currency: string;
  interval: string;
  features: string[] | null;
};

export type Subscription = {
  id: number;
  status: string;
  billing_cycle: "monthly" | "yearly" | null;
  is_active: boolean;
  trial_ends_at: string | null;
  ends_at: string | null;
};

export type User = {
  id: number;
  name: string;
  email: string;
  role: string;
  is_active: boolean;
  tenant_id: number | null;
  tenant?: Tenant | null;
};

export type LoginPayload = {
  user: User;
  token: string;
  token_type: "Bearer";
  expires_at: string | null;
};

export type DashboardStats = {
  current_month: { documents_count: number; documents_total: number };
  last_month: { documents_count: number; documents_total: number };
  by_status: { authorized: number; rejected: number; pending: number };
  by_type?: {
    facturas: number;
    liquidaciones: number;
    notas_credito: number;
    notas_debito: number;
    guias: number;
    retenciones: number;
    borradores: number;
    recibidos: number;
  };
  plan_usage: {
    documents_used: number;
    documents_limit: number;
    percentage: number;
  };
};

export type Company = {
  id: number;
  ruc: string;
  legal_name: string;
  trade_name?: string | null;
  sri_environment: string;
  branches?: Branch[];
};

export type Branch = {
  id: number;
  company_id: number;
  code: string;
  name: string;
  address?: string | null;
  is_main?: boolean;
  is_active?: boolean;
  emission_points?: EmissionPoint[];
  emission_points_count?: number;
};

export type EmissionPoint = {
  id: number;
  branch_id: number;
  code: string;
  description?: string | null;
  is_active?: boolean;
  branch?: Branch;
};

export type TaxRate = {
  code: string;
  percentage_code: string;
  rate: number;
  label: string;
};

export type PaymentMethod = {
  code: string;
  label: string;
};

export type Category = {
  id: number;
  parent_id: number | null;
  name: string;
  slug?: string | null;
  description?: string | null;
  color?: string | null;
  icon?: string | null;
  sort_order: number;
  is_active: boolean;
  is_root?: boolean;
  full_path?: string;
  product_count?: number;
};

export type InventoryMovement = {
  id: number;
  product_id: number;
  movement_type: string;
  movement_type_label?: string;
  movement_type_icon?: string;
  movement_type_color?: string;
  quantity: number;
  absolute_quantity?: number;
  stock_before?: number;
  stock_after?: number;
  unit_cost?: number | null;
  total_cost?: number | null;
  batch_number?: string | null;
  expiry_date?: string | null;
  notes?: string | null;
  reference_type?: string | null;
  reference_id?: number | null;
  is_incoming?: boolean;
  is_outgoing?: boolean;
  created_at: string;
  product?: Product;
  created_by?: { id: number; name: string } | null;
};

export type InventorySummary = {
  summary: {
    total_products_with_inventory: number;
    low_stock_count: number;
    out_of_stock_count: number;
    total_inventory_value: number;
  };
  recent_movements: InventoryMovement[];
};

export type PosSession = {
  id: number;
  branch_id: number;
  emission_point_id: number;
  status: "open" | "closed" | string;
  opening_amount?: number;
  closing_amount?: number | null;
  expected_amount?: number | null;
  difference?: number | null;
  closing_notes?: string | null;
  opened_at: string;
  closed_at?: string | null;
  total_sales?: number;
  total_transactions?: number;
  branch?: Branch;
  emission_point?: EmissionPoint;
  user?: { id: number; name: string };
};

export type PosTransactionItem = {
  id: number;
  product_id: number | null;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  subtotal: number;
  tax_rate: number;
  tax_value: number;
  product?: { id: number; name: string; code?: string };
};

export type PosTransaction = {
  id: number;
  session_id: number;
  customer_id?: number | null;
  payment_method: string;
  amount_received?: number | null;
  change_amount?: number | null;
  total: number;
  status: string;
  notes?: string | null;
  created_at: string;
  customer?: Customer | null;
  items?: PosTransactionItem[];
};

export type Quote = {
  id: number;
  quote_number: string;
  status: string;
  status_label?: string | null;
  issue_date: string;
  expiry_date?: string | null;
  subtotal: number;
  total_discount: number;
  total_tax: number;
  total: number;
  notes?: string | null;
  payment_terms?: string | null;
  converted_to_document_id?: number | null;
  customer?: Customer;
  company_id: number;
  customer_id: number;
  items?: Array<{
    id: number;
    product_id: number | null;
    description: string;
    quantity: number;
    unit_price: number;
    discount: number;
    tax_rate: number;
    subtotal: number;
    tax_value: number;
    total: number;
  }>;
};

export type ReceivedDocument = {
  id: number;
  company_id: number;
  document_type: string;
  access_key?: string | null;
  authorization_number?: string | null;
  authorization_date?: string | null;
  issuer_ruc: string;
  issuer_name: string;
  issue_date: string;
  subtotal_0: number;
  subtotal_5: number;
  subtotal_12: number;
  subtotal_15: number;
  subtotal_no_tax: number;
  total_discount: number;
  total_tax: number;
  total: number;
  expense_category?: string | null;
  is_processed: boolean;
  notes?: string | null;
};

export type PersonalExpense = {
  id: number;
  fiscal_year: number;
  category: string;
  description: string;
  issuer_ruc?: string | null;
  issuer_name?: string | null;
  document_number?: string | null;
  issue_date: string;
  amount: number;
  notes?: string | null;
};

export type RecurringInvoiceItem = {
  product_id?: number | null;
  description: string;
  quantity: number;
  unit_price: number;
  tax_rate?: number;
};

export type RecurringInvoice = {
  id: number;
  company_id: number;
  branch_id: number;
  emission_point_id: number;
  customer_id: number;
  frequency: string;
  start_date: string;
  end_date?: string | null;
  next_issue_date: string;
  status: string;
  items: RecurringInvoiceItem[];
  total_issued: number;
  max_issues?: number | null;
  notes?: string | null;
  customer?: Customer;
};

export type SupportTicket = {
  id: number;
  subject: string;
  category: string;
  priority: string;
  status: string;
  resolved_at?: string | null;
  user?: { id: number; name: string };
  assigned_to?: number | null;
  messages?: Array<{
    id: number;
    user_id: number;
    is_admin_reply: boolean;
    message: string;
    created_at: string;
    user?: { id: number; name: string };
  }>;
  created_at: string;
};

export type AccountingAccount = {
  id: number;
  code: string;
  name: string;
  account_type: "activo" | "pasivo" | "patrimonio" | "ingreso" | "costo" | "gasto";
  account_nature: "debit" | "credit";
  parent_id: number | null;
  allows_movement: boolean;
  tax_form_code?: string | null;
  description?: string | null;
  level?: number;
  is_active?: boolean;
  current_balance?: number;
  parent?: AccountingAccount;
  children?: AccountingAccount[];
};

export type JournalEntryLine = {
  id: number;
  account_id: number;
  cost_center_id?: number | null;
  debit: number;
  credit: number;
  description?: string | null;
  account?: AccountingAccount;
};

export type JournalEntry = {
  id: number;
  entry_number?: string;
  entry_date: string;
  description?: string | null;
  status: "draft" | "posted" | "void" | string;
  total_debit: number;
  total_credit: number;
  fiscal_period_id?: number;
  reference_type?: string | null;
  reference_id?: number | null;
  posted_at?: string | null;
  voided_at?: string | null;
  void_reason?: string | null;
  lines?: JournalEntryLine[];
};

export type FiscalPeriod = {
  id: number;
  year: number;
  month?: number | null;
  start_date: string;
  end_date: string;
  status: "open" | "closed" | "locked" | string;
  closed_at?: string | null;
  locked_at?: string | null;
};

export type CostCenter = {
  id: number;
  code: string;
  name: string;
  description?: string | null;
  is_active: boolean;
};

export type Budget = {
  id: number;
  name: string;
  year: number;
  status: "draft" | "approved" | "active" | "closed" | string;
  total_amount?: number;
  approved_at?: string | null;
  activated_at?: string | null;
  closed_at?: string | null;
};

export type TaxFormSubmission = {
  id: number;
  form_type: string;
  period?: string;
  year?: number;
  month?: number;
  status?: string;
  generated_at?: string | null;
  total_amount?: number;
};

export type PurchaseItem = {
  id: number;
  product_id: number | null;
  main_code?: string | null;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  subtotal: number;
  tax_rate: number;
  tax_percentage_code?: string | null;
  tax_value: number;
};

export type Purchase = {
  id: number;
  tenant_id?: number;
  company_id: number;
  supplier_id: number;
  document_type: string;
  supplier_document_number: string;
  supplier_authorization?: string | null;
  issue_date: string;
  authorization_date?: string | null;
  subtotal_no_tax?: number;
  subtotal_0: number;
  subtotal_5: number;
  subtotal_12: number;
  subtotal_15: number;
  total_discount: number;
  total_tax: number;
  total: number;
  status: string;
  payment_methods?: Array<{ code: string; amount: number }> | null;
  notes?: string | null;
  attachment_path?: string | null;
  created_at?: string;
  updated_at?: string;
  supplier?: Supplier;
  company?: Company;
  items?: PurchaseItem[];
};

export type Supplier = {
  id: number;
  identification_type: string;
  identification: string;
  business_name: string;
  commercial_name?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  city?: string | null;
  is_active: boolean;
  is_withholding_agent?: boolean;
  accounting_account?: string | null;
  notes?: string | null;
  total_purchased?: number;
  last_purchase_date?: string | null;
};

export type Customer = {
  id: number;
  identification_type: string;
  identification_type_label?: string;
  identification_number: string;
  name: string;
  email?: string | null;
  additional_emails?: string[] | null;
  phone?: string | null;
  address?: string | null;
  economic_activity?: string | null;
  is_active: boolean;
  documents_count?: number;
};

export type Product = {
  id: number;
  code: string;
  sku?: string | null;
  name: string;
  description?: string | null;
  type: "product" | "service";
  type_label?: string;
  category_id?: number | null;
  category?: string | null;
  unit_price: number;
  cost?: number;
  tax_code?: string | null;
  tax_percentage_code?: string | null;
  tax_rate: number;
  track_inventory: boolean;
  stock: number | null;
  min_stock?: number | null;
  is_active: boolean;
};

export type DocumentItem = {
  id: number;
  product_id: number | null;
  main_code: string;
  aux_code?: string | null;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  subtotal: number;
  tax_rate: number;
  tax_percentage_code?: string | null;
  tax_value: number;
};

export type WithholdingDetail = {
  id: number;
  support_doc_code: string;
  support_doc_number: string;
  support_doc_date?: string | null;
  support_doc_total: number;
  tax_type: string;
  retention_code: string;
  tax_base: number;
  retention_rate: number;
  retained_value: number;
};

export type Document = {
  id: number;
  document_type?: string;
  document_type_label?: string;
  type?: string;
  document_number?: string;
  number?: string;
  access_key?: string | null;
  additional_info?: Record<string, unknown> | null;
  email_sent?: boolean;
  email_sent_at?: string | null;
  email_sent_to?: string | null;
  environment?: string;
  environment_label?: string;
  series?: string;
  sequential?: string;
  issue_date?: string;
  issue_datetime?: string;
  date?: string;
  currency?: string;
  subtotal_no_tax?: number;
  subtotal_0?: number;
  subtotal_5?: number;
  subtotal_8?: number;
  subtotal_12?: number;
  subtotal_13?: number;
  subtotal_15?: number;
  total_discount?: number;
  total_tax?: number;
  tip?: number;
  total: number;
  payment_methods?: Array<{ code: string; amount: number; term?: number }> | null;
  status: string;
  status_label?: string;
  status_color?: string;
  authorization_number?: string | null;
  authorization_date?: string | null;
  sri_messages?: unknown;
  contingency_active?: boolean;
  contingency_message?: string | null;
  has_ride?: boolean;
  has_xml?: boolean;
  customer?: Customer | null;
  customer_name?: string;
  customer_identification?: string;
  company?: {
    id: number;
    ruc: string;
    business_name: string;
    trade_name?: string | null;
    address?: string;
    email?: string;
    logo_url?: string | null;
  } | null;
  items?: DocumentItem[];
  withholding_details?: WithholdingDetail[];
  created_at?: string;
  updated_at?: string;
};
