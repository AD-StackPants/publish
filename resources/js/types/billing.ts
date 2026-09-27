export type PlanType = 'base_plan' | 'addon';

export type BillingInterval = 'month' | 'year' | 'one_time';

export type PlanFeatures = {
    channels_limit?: number;
    allows_scheduling?: boolean;
    priority_worker?: boolean;
    custom_domain?: boolean;
    team_members?: number;
    description?: string;
    [key: string]: unknown;
};

export type Plan = {
    id: string;
    slug: string;
    name: string;
    type: PlanType;
    price_cents: number;
    billing_interval: BillingInterval;
    features: PlanFeatures;
    is_active: boolean;
};

export type ActiveAddon = {
    plan_id: string;
    slug: string;
    name: string;
    unit_price_cents: number;
    quantity: number;
};

export type CartItem = {
    plan_id: string;
    slug: string;
    name: string;
    unit_price_cents: number;
    quantity: number;
};

export type InvoiceLineItem = {
    id: string;
    description: string;
    quantity: number;
    unit_price_cents: number;
    amount_cents: number;
};

export type Invoice = {
    id: string;
    invoice_number: string;
    external_invoice_id?: string | null;
    subtotal_cents: number;
    total_cents: number;
    currency: string;
    status: 'paid' | 'open' | 'void' | 'uncollectible';
    pdf_url?: string | null;
    paid_at?: string | null;
    created_at: string;
    line_items: InvoiceLineItem[];
};

export type SubscriptionDetails = {
    currentPlan: Plan;
    status: 'active' | 'past_due' | 'canceled' | 'trialing';
    periodStart: string;
    periodEnd: string;
    cancelAtPeriodEnd: boolean;
    activeAddons: ActiveAddon[];
    invoices: Invoice[];
    is_bypassed?: boolean;
    can_bypass_subscription?: boolean;
};
