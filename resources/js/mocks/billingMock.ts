import type {
    Plan,
    SubscriptionDetails,
    CartItem,
    Invoice,
} from '../types/billing';

export const INITIAL_PLANS: Plan[] = [
    {
        id: 'plan-free-uuid',
        slug: 'plan-free',
        name: 'Free Plan',
        type: 'base_plan',
        price_cents: 0,
        billing_interval: 'month',
        features: {
            channels_limit: 1,
            allows_scheduling: false,
            description:
                'Basic social connectivity for hobbyists and individual creators.',
        },
        is_active: true,
    },
    {
        id: 'plan-pro-uuid',
        slug: 'plan-pro',
        name: 'Pro Plan',
        type: 'base_plan',
        price_cents: 1900,
        billing_interval: 'month',
        features: {
            channels_limit: 5,
            allows_scheduling: true,
            team_members: 5,
            description:
                'Essential toolkit for fast-growing brands and professional creators.',
        },
        is_active: true,
    },
    {
        id: 'plan-agency-uuid',
        slug: 'plan-agency',
        name: 'Agency Plan',
        type: 'base_plan',
        price_cents: 4900,
        billing_interval: 'month',
        features: {
            channels_limit: 15,
            allows_scheduling: true,
            team_members: 25,
            priority_worker: true,
            description:
                'High-throughput publishing cluster for agencies and multi-brand teams.',
        },
        is_active: true,
    },
    {
        id: 'addon-extra-channels-uuid',
        slug: 'addon-extra-channels',
        name: 'Extra Channel Pack (+3)',
        type: 'addon',
        price_cents: 500,
        billing_interval: 'month',
        features: {
            channels_limit: 3,
            description:
                'Expand your publishing reach with 3 additional connected social channels.',
        },
        is_active: true,
    },
    {
        id: 'addon-priority-worker-uuid',
        slug: 'addon-priority-worker',
        name: 'Dedicated Priority Worker',
        type: 'addon',
        price_cents: 1000,
        billing_interval: 'month',
        features: {
            priority_worker: true,
            description:
                'Dedicated isolated queue runner with sub-100ms instant dispatch SLA.',
        },
        is_active: true,
    },
    {
        id: 'addon-custom-domain-uuid',
        slug: 'addon-custom-domain',
        name: 'Custom Short Domain',
        type: 'addon',
        price_cents: 500,
        billing_interval: 'month',
        features: {
            custom_domain: true,
            description:
                'Branded link shortener (e.g. yourbrnd.co) with custom OpenGraph attribution.',
        },
        is_active: true,
    },
];

const PRO_PLAN = INITIAL_PLANS.find((p) => p.slug === 'plan-pro')!;

export const INITIAL_SUBSCRIPTION: SubscriptionDetails = {
    currentPlan: PRO_PLAN,
    status: 'active',
    periodStart: '2026-09-15T00:00:00Z',
    periodEnd: '2026-10-15T23:59:59Z',
    cancelAtPeriodEnd: false,
    activeAddons: [
        {
            plan_id: 'addon-extra-channels-uuid',
            slug: 'addon-extra-channels',
            name: 'Extra Channel Pack (+3)',
            unit_price_cents: 500,
            quantity: 1,
        },
    ],
    invoices: [
        {
            id: 'inv-2026-008',
            invoice_number: 'INV-2026-008',
            external_invoice_id: 'in_1PZ9x82eZvKYlo2C8',
            subtotal_cents: 2400,
            total_cents: 2400,
            currency: 'USD',
            status: 'paid',
            pdf_url: 'https://example.com/invoices/INV-2026-008.pdf',
            paid_at: '2026-09-15T08:12:00Z',
            created_at: '2026-09-15T08:12:00Z',
            line_items: [
                {
                    id: 'li-1',
                    description: 'Pro Plan - Monthly Subscription',
                    quantity: 1,
                    unit_price_cents: 1900,
                    amount_cents: 1900,
                },
                {
                    id: 'li-2',
                    description: 'Extra Channel Pack (+3 Channels)',
                    quantity: 1,
                    unit_price_cents: 500,
                    amount_cents: 500,
                },
            ],
        },
        {
            id: 'inv-2026-003',
            invoice_number: 'INV-2026-003',
            external_invoice_id: 'in_1PY3w71eZvKYlo2B1',
            subtotal_cents: 2400,
            total_cents: 2400,
            currency: 'USD',
            status: 'paid',
            pdf_url: 'https://example.com/invoices/INV-2026-003.pdf',
            paid_at: '2026-08-15T08:12:00Z',
            created_at: '2026-08-15T08:12:00Z',
            line_items: [
                {
                    id: 'li-3',
                    description: 'Pro Plan - Monthly Subscription',
                    quantity: 1,
                    unit_price_cents: 1900,
                    amount_cents: 1900,
                },
                {
                    id: 'li-4',
                    description: 'Extra Channel Pack (+3 Channels)',
                    quantity: 1,
                    unit_price_cents: 500,
                    amount_cents: 500,
                },
            ],
        },
    ],
};

function getStorageKey(tenantId: string): string {
    return `demo_billing_${tenantId}`;
}

export function getTenantSubscription(tenantId: string): SubscriptionDetails {
    const key = getStorageKey(tenantId);
    try {
        const raw = localStorage.getItem(key);
        if (raw) {
            return JSON.parse(raw);
        }
    } catch {
        // fallback
    }
    // initialize
    localStorage.setItem(key, JSON.stringify(INITIAL_SUBSCRIPTION));
    return JSON.parse(JSON.stringify(INITIAL_SUBSCRIPTION));
}

export function saveTenantSubscription(
    tenantId: string,
    sub: SubscriptionDetails,
): void {
    try {
        localStorage.setItem(getStorageKey(tenantId), JSON.stringify(sub));
    } catch (e) {
        console.warn('Failed to save subscription in localStorage', e);
    }
}

export function handleBillingRoute(
    pathname: string,
    method: string,
    body: any,
    tenantId: string,
): { status: number; data: any } | null {
    if (!pathname.startsWith('/api/v1/billing')) {
        return null;
    }

    // 1. GET /api/v1/billing/plans
    if (pathname === '/api/v1/billing/plans' && method === 'get') {
        const basePlans = INITIAL_PLANS.filter((p) => p.type === 'base_plan');
        const addons = INITIAL_PLANS.filter((p) => p.type === 'addon');
        return {
            status: 200,
            data: {
                plans: INITIAL_PLANS,
                base_plans: basePlans,
                addons: addons,
            },
        };
    }

    // 2. GET /api/v1/billing/subscription
    if (pathname === '/api/v1/billing/subscription' && method === 'get') {
        const sub = getTenantSubscription(tenantId);
        return {
            status: 200,
            data: sub,
        };
    }

    // 3. POST /api/v1/billing/change-plan
    if (pathname === '/api/v1/billing/change-plan' && method === 'post') {
        const sub = getTenantSubscription(tenantId);
        const targetPlanId = body?.plan_id || body?.plan_slug;
        const newPlan = INITIAL_PLANS.find(
            (p) =>
                p.type === 'base_plan' &&
                (p.id === targetPlanId || p.slug === targetPlanId),
        );

        if (!newPlan) {
            return {
                status: 400,
                data: { message: 'Invalid or unknown base plan specified.' },
            };
        }

        sub.currentPlan = newPlan;

        // Generate prorated / adjustment invoice if upgrading
        const randomNum = Math.floor(100 + Math.random() * 900);
        const invNumber = `INV-2026-${randomNum}`;
        const newInvoice: Invoice = {
            id: `inv-${Date.now()}`,
            invoice_number: invNumber,
            external_invoice_id: `in_${Date.now()}`,
            subtotal_cents: newPlan.price_cents,
            total_cents: newPlan.price_cents,
            currency: 'USD',
            status: 'paid',
            pdf_url: `https://example.com/invoices/${invNumber}.pdf`,
            paid_at: new Date().toISOString(),
            created_at: new Date().toISOString(),
            line_items: [
                {
                    id: `li-${Date.now()}`,
                    description: `${newPlan.name} - Plan Switch Proration`,
                    quantity: 1,
                    unit_price_cents: newPlan.price_cents,
                    amount_cents: newPlan.price_cents,
                },
            ],
        };

        sub.invoices.unshift(newInvoice);
        saveTenantSubscription(tenantId, sub);

        return {
            status: 200,
            data: {
                success: true,
                message: `Successfully updated subscription to ${newPlan.name}.`,
                subscription: sub,
            },
        };
    }

    // 4. POST /api/v1/billing/cart/checkout
    if (pathname === '/api/v1/billing/cart/checkout' && method === 'post') {
        const sub = getTenantSubscription(tenantId);
        const cartItems: CartItem[] = body?.items || [];

        if (!cartItems.length) {
            return {
                status: 400,
                data: { message: 'Cart cannot be empty.' },
            };
        }

        let addedTotalCents = 0;
        const lineItems: Invoice['line_items'] = [];

        // Apply cart items to activeAddons
        for (const item of cartItems) {
            const addonPlan = INITIAL_PLANS.find(
                (p) => p.id === item.plan_id || p.slug === item.slug,
            );
            const priceCents = addonPlan
                ? addonPlan.price_cents
                : item.unit_price_cents;
            const itemTotal = priceCents * item.quantity;
            addedTotalCents += itemTotal;

            const existingIndex = sub.activeAddons.findIndex(
                (a) => a.slug === item.slug,
            );
            if (existingIndex !== -1) {
                // Increment or update quantity
                sub.activeAddons[existingIndex].quantity += item.quantity;
            } else {
                sub.activeAddons.push({
                    plan_id: item.plan_id,
                    slug: item.slug,
                    name: item.name,
                    unit_price_cents: priceCents,
                    quantity: item.quantity,
                });
            }

            lineItems.push({
                id: `li-${Date.now()}-${item.slug}`,
                description: `${item.name} (x${item.quantity})`,
                quantity: item.quantity,
                unit_price_cents: priceCents,
                amount_cents: itemTotal,
            });
        }

        // Generate paid invoice for the add-on checkout
        const randomNum = Math.floor(100 + Math.random() * 900);
        const invNumber = `INV-2026-${randomNum}`;
        const newInvoice: Invoice = {
            id: `inv-${Date.now()}`,
            invoice_number: invNumber,
            external_invoice_id: `in_${Date.now()}`,
            subtotal_cents: addedTotalCents,
            total_cents: addedTotalCents,
            currency: 'USD',
            status: 'paid',
            pdf_url: `https://example.com/invoices/${invNumber}.pdf`,
            paid_at: new Date().toISOString(),
            created_at: new Date().toISOString(),
            line_items: lineItems,
        };

        sub.invoices.unshift(newInvoice);
        saveTenantSubscription(tenantId, sub);

        return {
            status: 200,
            data: {
                success: true,
                message: 'Add-on order checked out successfully.',
                subscription: sub,
                invoice: newInvoice,
            },
        };
    }

    return null;
}
