import {
    ArrowLeft,
    ArrowRight,
    BookOpen,
    CheckCircle2,
    Download,
    Mail,
    Package,
    ShoppingCart,
    Ticket,
    UserRound,
    Users,
} from 'lucide-react';
import { startTransition, useDeferredValue, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';
import { formatCurrency } from '../mocks/adminOrderMockData';
import { getOrderDeliveryMeta } from '../utils/orderDelivery';

const steps = [
    { key: 'customer', label: 'Who is buying?' },
    { key: 'sale', label: 'What are you selling?' },
    { key: 'item', label: 'Select item' },
    { key: 'details', label: 'Sale details' },
    { key: 'review', label: 'Review and create' },
];

function Field({ children, label }) {
    return (
        <label className="block">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">{label}</span>
            {children}
        </label>
    );
}

function Input(props) {
    return <input className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]" {...props} />;
}

function Select(props) {
    return <select className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]" {...props} />;
}

function TextArea(props) {
    return <textarea className="min-h-28 w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]" {...props} />;
}

function StepButton({ index, isActive, isComplete, label, onClick }) {
    return (
        <button className={['flex min-w-[148px] items-center gap-3 rounded-[1.35rem] border px-4 py-3 text-left transition sm:min-w-[170px]', isActive ? 'border-[color:var(--admin-accent)] bg-[color:var(--admin-accent-soft)]' : 'border-[color:var(--admin-border)] bg-white hover:border-[color:var(--admin-accent)]'].join(' ')} onClick={onClick} type="button">
            <span className={['flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold', isActive || isComplete ? 'bg-[color:var(--admin-ink)] text-white' : 'bg-[color:var(--admin-surface)] text-[color:var(--admin-muted)]'].join(' ')}>
                {isComplete && !isActive ? <CheckCircle2 className="h-4.5 w-4.5" /> : index}
            </span>
            <span className="text-sm font-semibold text-[color:var(--admin-ink)]">{label}</span>
        </button>
    );
}

function ChoiceCard({ description, icon: Icon, meta, onClick, selected, title }) {
    return (
        <button className={['w-full rounded-[1.5rem] border px-4 py-4 text-left transition', selected ? 'border-[color:var(--admin-accent)] bg-[color:var(--admin-accent-soft)] shadow-sm' : 'border-[color:var(--admin-border)] bg-white hover:border-[color:var(--admin-accent)] hover:bg-[color:var(--admin-surface)]'].join(' ')} onClick={onClick} type="button">
            <div className="flex items-start gap-3">
                <span className={['flex h-11 w-11 shrink-0 items-center justify-center rounded-[1rem]', selected ? 'bg-[color:var(--admin-ink)] text-white' : 'bg-[color:var(--admin-surface)] text-[color:var(--admin-accent)]'].join(' ')}>
                    <Icon className="h-5 w-5" />
                </span>
                <span className="block min-w-0">
                    <span className="block text-sm font-semibold text-[color:var(--admin-ink)]">{title}</span>
                    <span className="mt-1 block text-sm leading-6 text-[color:var(--admin-muted)]">{description}</span>
                    {meta ? <span className="mt-2 block text-sm font-medium text-[color:var(--admin-ink)]">{meta}</span> : null}
                </span>
            </div>
        </button>
    );
}

function ResultCard({ deliveryError, feedback, isSendingDelivery, onSendDelivery, sale }) {
    const deliveryMeta = getOrderDeliveryMeta(sale.order);

    return (
        <div className="rounded-[1.75rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{sale.order.reference}</p>
                    <p className="mt-1 text-sm leading-6 text-[color:var(--admin-muted)]">{sale.order.customerName} | {formatCurrency(sale.order.total, sale.order.currency)}</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <AdminStatusBadge value={sale.order.status} />
                    <AdminStatusBadge value={sale.order.paymentStatus} />
                    <AdminStatusBadge value={sale.order.source} />
                </div>
            </div>
            <div className="mt-4 space-y-2">
                {sale.order.lines.map((line) => (
                    <div className="rounded-2xl bg-white px-4 py-3" key={`${sale.order.id}-${line.label}`}>
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{line.label}</p>
                        <p className="mt-1 text-sm leading-6 text-[color:var(--admin-muted)]">Qty {line.quantity} | {formatCurrency(line.unitPrice, sale.order.currency)} each</p>
                    </div>
                ))}
            </div>
            {feedback ? (
                <div className="mt-4 rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm text-[color:var(--admin-accent)]">
                    {feedback}
                </div>
            ) : null}
            {deliveryError ? (
                <div className="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {deliveryError}
                </div>
            ) : null}
            {sale.tickets.length > 0 ? <div className="mt-4 flex flex-wrap gap-2">{sale.tickets.map((ticket) => <span className="rounded-full border border-[color:var(--admin-border)] bg-white px-3 py-2 text-sm font-medium text-[color:var(--admin-ink)]" key={ticket.id}>{ticket.code}</span>)}</div> : null}
            <div className="mt-5 flex flex-wrap gap-2">
                <Link className="inline-flex items-center justify-center rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black" to="/admin/orders">View order</Link>
                {sale.tickets.length > 0 ? <Link className="inline-flex items-center justify-center rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm font-semibold text-[color:var(--admin-ink)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]" to="/admin/tickets">View tickets</Link> : null}
                <button
                    className={[
                        'inline-flex items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold transition',
                        deliveryMeta.disabled
                            ? 'cursor-not-allowed border-stone-200 bg-stone-100 text-stone-400'
                            : 'border-[color:var(--admin-border)] bg-white text-[color:var(--admin-ink)] hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]',
                    ].join(' ')}
                    disabled={deliveryMeta.disabled || isSendingDelivery}
                    onClick={onSendDelivery}
                    type="button"
                >
                    <Mail className="h-4.5 w-4.5" />
                    <span>{isSendingDelivery ? 'Sending...' : deliveryMeta.actionLabel}</span>
                </button>
            </div>
            <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">{deliveryMeta.helperText}</p>
        </div>
    );
}

function getCustomerStepValid(form) {
    return form.customerMode === 'existing' ? Boolean(form.existingCustomerId && form.buyerName) : Boolean(form.buyerName && (form.phone || form.email));
}

function getItemStepValid(form, selectedTicketType, selectedBookFormat) {
    return form.saleType === 'ticket' ? Boolean(form.eventSlug && form.ticketType && selectedTicketType) : Boolean(form.bookSlug && form.bookFormat && selectedBookFormat);
}

function getDetailsStepValid(form) {
    if (Number(form.quantity) <= 0 || !form.paymentMethod || !form.issueStatus) {
        return false;
    }

    return form.priceMode === 'custom' ? Number(form.customUnitPrice) >= 0 : true;
}

export function AdminManualSalesPage() {
    const { books, createManualSale, customers, defaultManualSaleForm, events, readDataError, resendOrderDelivery } = useAdminMockData();
    const [form, setForm] = useState(defaultManualSaleForm);
    const [latestSale, setLatestSale] = useState(null);
    const [step, setStep] = useState(1);
    const [existingCustomerQuery, setExistingCustomerQuery] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isSendingLatestSale, setIsSendingLatestSale] = useState(false);
    const [latestSaleDeliveryFeedback, setLatestSaleDeliveryFeedback] = useState('');
    const [latestSaleDeliveryError, setLatestSaleDeliveryError] = useState('');
    const [submitError, setSubmitError] = useState('');
    const deferredCustomerQuery = useDeferredValue(existingCustomerQuery);

    const selectedEvent = events.find((event) => event.slug === form.eventSlug) ?? events[0];
    const availableTicketTypes = selectedEvent?.ticketTypes ?? [];
    const selectedTicketType = availableTicketTypes.find((ticketType) => ticketType.label === form.ticketType) ?? availableTicketTypes[0];
    const selectedBook = books.find((book) => book.slug === form.bookSlug) ?? books[0];
    const availableBookFormats = selectedBook?.formats ?? [];
    const selectedBookFormat = availableBookFormats.find((format) => format.label === form.bookFormat) ?? availableBookFormats[0];
    const standardUnitPrice = form.saleType === 'ticket' ? Number(selectedTicketType?.price ?? 0) : Number(selectedBookFormat?.price ?? 0);
    const activeUnitPrice = form.priceMode === 'custom' ? Number(form.customUnitPrice || 0) : standardUnitPrice;
    const total = activeUnitPrice * Number(form.quantity || 0);
    const selectedExistingCustomer = customers.find((customer) => customer.id === form.existingCustomerId) ?? null;
    const customerStepValid = getCustomerStepValid(form);
    const itemStepValid = getItemStepValid(form, selectedTicketType, selectedBookFormat);
    const detailsStepValid = getDetailsStepValid(form);
    const canAdvance = (step === 1 && customerStepValid) || (step === 2 && Boolean(form.saleType)) || (step === 3 && itemStepValid) || (step === 4 && detailsStepValid);

    const existingCustomerResults = useMemo(() => {
        if (!deferredCustomerQuery) {
            return customers.slice(0, 6);
        }

        return customers.filter((customer) => [customer.name, customer.email, customer.phone].join(' ').toLowerCase().includes(deferredCustomerQuery.toLowerCase())).slice(0, 6);
    }, [customers, deferredCustomerQuery]);

    useEffect(() => {
        if (form.saleType !== 'ticket' || !availableTicketTypes.length) {
            return;
        }

        const ticketTypeExists = availableTicketTypes.some((ticketType) => ticketType.label === form.ticketType);

        if (ticketTypeExists) {
            return;
        }

        setForm((current) => ({ ...current, ticketType: availableTicketTypes[0].label }));
    }, [availableTicketTypes, form.saleType, form.ticketType]);

    useEffect(() => {
        if (form.saleType !== 'book' || !books.length) {
            return;
        }

        const bookExists = books.some((book) => book.slug === form.bookSlug);

        if (!bookExists) {
            setForm((current) => ({
                ...current,
                bookSlug: books[0].slug,
                bookFormat: books[0].formats[0]?.label ?? '',
            }));
        }
    }, [books, form.bookSlug, form.saleType]);

    useEffect(() => {
        if (form.saleType !== 'book' || !availableBookFormats.length) {
            return;
        }

        const formatExists = availableBookFormats.some((format) => format.label === form.bookFormat);

        if (formatExists) {
            return;
        }

        setForm((current) => ({ ...current, bookFormat: availableBookFormats[0].label }));
    }, [availableBookFormats, form.bookFormat, form.saleType]);

    useEffect(() => {
        if (form.priceMode !== 'standard' || Number(form.customUnitPrice) === standardUnitPrice) {
            return;
        }

        setForm((current) => ({ ...current, customUnitPrice: standardUnitPrice }));
    }, [form.customUnitPrice, form.priceMode, standardUnitPrice]);

    const goToStep = (nextStep) => {
        if (nextStep < 1 || nextStep > steps.length) {
            return;
        }

        if (nextStep >= 2 && !customerStepValid) {
            return;
        }

        if (nextStep >= 3 && !form.saleType) {
            return;
        }

        if (nextStep >= 4 && !itemStepValid) {
            return;
        }

        if (nextStep >= 5 && !detailsStepValid) {
            return;
        }

        setStep(nextStep);
    };

    const selectExistingCustomer = (customer) => {
        setForm((current) => ({
            ...current,
            customerMode: 'existing',
            existingCustomerId: customer.id,
            buyerName: customer.name,
            email: customer.email,
            phone: customer.phone,
            customerType: customer.customerType,
            relationshipType: customer.relationshipType,
        }));
    };

    const handleCustomerMode = (mode) => {
        if (mode === 'existing') {
            setForm((current) => ({
                ...current,
                customerMode: 'existing',
                relationshipType: current.relationshipType === 'Walk-in' ? 'Existing' : current.relationshipType,
                customerType: current.customerType === 'Walk-in' ? 'Individual' : current.customerType,
            }));
            return;
        }

        setExistingCustomerQuery('');
        setForm((current) => ({
            ...current,
            customerMode: 'walk_in',
            existingCustomerId: '',
            customerType: 'Walk-in',
            relationshipType: 'Walk-in',
        }));
    };

    const handleSaleType = (saleType) => {
        setForm((current) => ({ ...current, saleType, priceMode: 'standard' }));
    };

    const buildResetForm = () => ({
        ...defaultManualSaleForm,
        saleType: form.saleType,
        eventSlug: form.eventSlug,
        ticketType: form.ticketType,
        bookSlug: form.bookSlug,
        bookFormat: form.bookFormat,
        paymentMethod: form.paymentMethod,
        issueStatus: form.issueStatus,
        customUnitPrice: standardUnitPrice,
    });

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSubmitError('');
        setIsSubmitting(true);

        try {
            const sale = await createManualSale({
                ...form,
                quantity: Number(form.quantity),
                customUnitPrice: Number(form.customUnitPrice),
            });

            startTransition(() => {
                setLatestSale({
                    order: sale.createdOrder,
                    tickets: sale.createdTickets,
                });
                setLatestSaleDeliveryFeedback('');
                setLatestSaleDeliveryError('');
                setForm(buildResetForm());
                setExistingCustomerQuery('');
                setStep(1);
            });
        } catch (error) {
            setSubmitError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleSendLatestSale = async () => {
        if (!latestSale?.order) {
            return;
        }

        setLatestSaleDeliveryError('');
        setIsSendingLatestSale(true);

        try {
            const response = await resendOrderDelivery(latestSale.order.id);
            const updatedOrder = response?.order ?? latestSale.order;
            const meta = getOrderDeliveryMeta(updatedOrder);

            setLatestSale((current) => (
                current
                    ? {
                          ...current,
                          order: updatedOrder,
                      }
                    : current
            ));
            setLatestSaleDeliveryFeedback(response?.message || meta.successFallback);
        } catch (error) {
            setLatestSaleDeliveryError(error.message);
        } finally {
            setIsSendingLatestSale(false);
        }
    };

    const reviewLabel = form.saleType === 'ticket' ? `${selectedEvent?.title} | ${selectedTicketType?.label}` : `${selectedBook?.title} | ${selectedBookFormat?.label}`;

    return (
        <div className="min-w-0 space-y-6">
            <AdminPageHeader title="Manual Sales" />

            {readDataError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {readDataError}
                </div>
            ) : null}

            {submitError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {submitError}
                </div>
            ) : null}

            <section className="-mx-1 overflow-x-auto px-1 pb-2">
                <div className="flex w-max gap-3 pr-4">
                    {steps.map((wizardStep, index) => (
                        <StepButton
                            index={index + 1}
                            isActive={step === index + 1}
                            isComplete={step > index + 1}
                            key={wizardStep.key}
                            label={wizardStep.label}
                            onClick={() => goToStep(index + 1)}
                        />
                    ))}
                </div>
            </section>

            <section className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div className="min-w-0 space-y-4">
                    <AdminSectionCard
                        eyebrow={`Step ${step}`}
                        icon={ShoppingCart}
                        title={steps[step - 1].label}
                    >
                        <form className="space-y-5" onSubmit={handleSubmit}>
                            {step === 1 ? (
                                <div className="space-y-5">
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <ChoiceCard description="Find a saved customer." icon={Users} onClick={() => handleCustomerMode('existing')} selected={form.customerMode === 'existing'} title="Existing customer" />
                                        <ChoiceCard description="Add a new customer for this sale." icon={UserRound} onClick={() => handleCustomerMode('walk_in')} selected={form.customerMode === 'walk_in'} title="Walk-in customer" />
                                    </div>

                                    {form.customerMode === 'existing' ? (
                                        <div className="space-y-4">
                                            <Field label="Find customer">
                                                <Input onChange={(event) => setExistingCustomerQuery(event.target.value)} placeholder="Search by name, email, or phone" type="search" value={existingCustomerQuery} />
                                            </Field>

                                            <div className="grid gap-3">
                                                {existingCustomerResults.map((customer) => (
                                                    <ChoiceCard
                                                        description={customer.email || customer.phone || 'No contact recorded'}
                                                        icon={Users}
                                                        key={customer.id}
                                                        meta={`${formatCurrency(customer.totalSpent)} spent`}
                                                        onClick={() => selectExistingCustomer(customer)}
                                                        selected={form.existingCustomerId === customer.id}
                                                        title={customer.name}
                                                    />
                                                ))}
                                            </div>

                                            {selectedExistingCustomer ? (
                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <Field label="Customer name">
                                                        <Input onChange={(event) => setForm((current) => ({ ...current, buyerName: event.target.value }))} type="text" value={form.buyerName} />
                                                    </Field>
                                                    <Field label="Email">
                                                        <Input onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))} type="email" value={form.email} />
                                                    </Field>
                                                    <Field label="Phone">
                                                        <Input onChange={(event) => setForm((current) => ({ ...current, phone: event.target.value }))} type="tel" value={form.phone} />
                                                    </Field>
                                                </div>
                                            ) : null}
                                        </div>
                                    ) : (
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <Field label="Customer name">
                                                <Input onChange={(event) => setForm((current) => ({ ...current, buyerName: event.target.value }))} placeholder="Walk-in customer" required type="text" value={form.buyerName} />
                                            </Field>
                                            <Field label="Phone">
                                                <Input onChange={(event) => setForm((current) => ({ ...current, phone: event.target.value }))} placeholder="+260..." type="tel" value={form.phone} />
                                            </Field>
                                            <Field label="Email">
                                                <Input onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))} placeholder="Optional" type="email" value={form.email} />
                                            </Field>
                                        </div>
                                    )}
                                </div>
                            ) : null}

                            {step === 2 ? (
                                <div className="grid gap-3 md:grid-cols-2">
                                    <ChoiceCard description="Sell event tickets." icon={Ticket} onClick={() => handleSaleType('ticket')} selected={form.saleType === 'ticket'} title="Tickets" />
                                    <ChoiceCard description="Sell books." icon={BookOpen} onClick={() => handleSaleType('book')} selected={form.saleType === 'book'} title="Books" />
                                </div>
                            ) : null}

                            {step === 3 ? (
                                <div className="space-y-5">
                                    {form.saleType === 'ticket' ? (
                                        <>
                                            <div className="space-y-3">
                                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">Select event</p>
                                                <div className="grid gap-3 md:grid-cols-2">
                                                    {events.map((event) => (
                                                        <ChoiceCard
                                                            description={`${event.dateLabel} | ${event.venue}`}
                                                            icon={Ticket}
                                                            key={event.slug}
                                                            onClick={() => setForm((current) => ({ ...current, eventSlug: event.slug, ticketType: event.ticketTypes[0]?.label ?? current.ticketType }))}
                                                            selected={form.eventSlug === event.slug}
                                                            title={event.title}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                            <div className="space-y-3">
                                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">Ticket type</p>
                                                <div className="grid gap-3 md:grid-cols-2">
                                                    {availableTicketTypes.map((ticketType) => (
                                                        <ChoiceCard
                                                            description={`Standard price ${formatCurrency(ticketType.price)}`}
                                                            icon={Ticket}
                                                            key={ticketType.label}
                                                            onClick={() => setForm((current) => ({ ...current, ticketType: ticketType.label }))}
                                                            selected={form.ticketType === ticketType.label}
                                                            title={ticketType.label}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            <div className="space-y-3">
                                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">Select book</p>
                                                <div className="grid gap-3 md:grid-cols-2">
                                                    {books.map((book) => (
                                                        <ChoiceCard
                                                            description={`Buyer types: ${book.allowedBuyerTypes.join(', ')}`}
                                                            icon={BookOpen}
                                                            key={book.slug}
                                                            onClick={() => setForm((current) => ({ ...current, bookSlug: book.slug, bookFormat: book.formats[0]?.label ?? current.bookFormat }))}
                                                            selected={form.bookSlug === book.slug}
                                                            title={book.title}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                            <div className="space-y-3">
                                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">Format</p>
                                                <div className="grid gap-3 md:grid-cols-2">
                                                    {availableBookFormats.map((format) => (
                                                        <ChoiceCard
                                                            description={format.fulfillment}
                                                            icon={format.label === 'Digital' ? Download : Package}
                                                            key={format.label}
                                                            meta={`Standard price ${formatCurrency(format.price)}`}
                                                            onClick={() => setForm((current) => ({ ...current, bookFormat: format.label }))}
                                                            selected={form.bookFormat === format.label}
                                                            title={format.label}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                        </>
                                    )}
                                </div>
                            ) : null}

                            {step === 4 ? (
                                <div className="space-y-5">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label="Quantity">
                                            <Input min="1" onChange={(event) => setForm((current) => ({ ...current, quantity: event.target.value }))} required type="number" value={form.quantity} />
                                        </Field>
                                        <Field label="Price mode">
                                            <Select onChange={(event) => setForm((current) => ({ ...current, priceMode: event.target.value }))} value={form.priceMode}>
                                                <option value="standard">Standard</option>
                                                <option value="custom">Custom</option>
                                            </Select>
                                        </Field>
                                        <Field label="Payment method">
                                            <Select onChange={(event) => setForm((current) => ({ ...current, paymentMethod: event.target.value }))} value={form.paymentMethod}>
                                                <option value="Cash">Cash</option>
                                                <option value="Mobile Money">Mobile Money</option>
                                                <option value="Card">Card</option>
                                                <option value="Complimentary">Complimentary</option>
                                            </Select>
                                        </Field>
                                        <Field label={form.saleType === 'ticket' ? 'Issue as' : 'Record as'}>
                                            <Select onChange={(event) => setForm((current) => ({ ...current, issueStatus: event.target.value }))} value={form.issueStatus}>
                                                <option value="paid">Paid</option>
                                                <option value="unpaid">Unpaid</option>
                                                <option value="reserved">Reserved</option>
                                            </Select>
                                        </Field>
                                    </div>

                                    {form.priceMode === 'custom' ? (
                                        <Field label="Custom unit price">
                                            <Input min="0" onChange={(event) => setForm((current) => ({ ...current, customUnitPrice: event.target.value }))} required step="1" type="number" value={form.customUnitPrice} />
                                        </Field>
                                    ) : null}

                                    <Field label="Notes">
                                        <TextArea onChange={(event) => setForm((current) => ({ ...current, notes: event.target.value }))} placeholder="Add a note" value={form.notes} />
                                    </Field>
                                </div>
                            ) : null}

                            {step === 5 ? (
                                <div className="space-y-5">
                                    <div className="rounded-[1.5rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] p-5">
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">Customer</p>
                                                <p className="mt-3 text-sm font-semibold text-[color:var(--admin-ink)]">{form.buyerName}</p>
                                                <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{form.email || 'No email recorded'}</p>
                                                <p className="text-sm text-[color:var(--admin-muted)]">{form.phone || 'No phone recorded'}</p>
                                                <div className="mt-3 flex flex-wrap gap-2">
                                                    <AdminStatusBadge value={form.customerType} />
                                                    <AdminStatusBadge value={form.relationshipType} />
                                                </div>
                                            </div>
                                            <div>
                                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">Sale</p>
                                                <p className="mt-3 text-sm font-semibold text-[color:var(--admin-ink)]">{reviewLabel}</p>
                                                <p className="mt-1 text-sm text-[color:var(--admin-muted)]">Qty {form.quantity} | {formatCurrency(activeUnitPrice)} each</p>
                                                <p className="mt-3 text-lg font-semibold text-[color:var(--admin-ink)]">Total {formatCurrency(total)}</p>
                                                <div className="mt-3 flex flex-wrap gap-2">
                                                    <AdminStatusBadge value={form.paymentMethod} />
                                                    <AdminStatusBadge value={form.issueStatus === 'paid' ? 'paid' : 'pending'} />
                                                    <AdminStatusBadge value="admin_manual" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button className="inline-flex w-full items-center justify-center rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-70" disabled={isSubmitting} type="submit">
                                        {isSubmitting ? 'Creating manual sale...' : 'Create manual sale'}
                                    </button>
                                </div>
                            ) : null}

                            <div className="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-between">
                                <button className="inline-flex items-center justify-center gap-2 rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm font-semibold text-[color:var(--admin-ink)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]" disabled={step === 1} onClick={() => goToStep(step - 1)} type="button">
                                    <ArrowLeft className="h-4.5 w-4.5" />
                                    <span>Back</span>
                                </button>

                                {step < 5 ? (
                                    <button className={['inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold text-white transition', canAdvance ? 'bg-[color:var(--admin-ink)] hover:bg-black' : 'cursor-not-allowed bg-stone-300'].join(' ')} disabled={!canAdvance} onClick={() => goToStep(step + 1)} type="button">
                                        <span>Continue</span>
                                        <ArrowRight className="h-4.5 w-4.5" />
                                    </button>
                                ) : null}
                            </div>
                        </form>
                    </AdminSectionCard>
                </div>

                <div className="min-w-0 space-y-4">
                    <AdminSectionCard eyebrow="Summary" icon={ShoppingCart} title="Current sale">
                        <div className="space-y-4">
                            <div className="rounded-2xl bg-[color:var(--admin-surface)] px-4 py-4">
                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">Buyer path</p>
                                <p className="mt-3 text-sm font-semibold text-[color:var(--admin-ink)]">{form.customerMode === 'existing' ? 'Existing customer' : 'Walk-in customer'}</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    <AdminStatusBadge value={form.customerType} />
                                    <AdminStatusBadge value={form.relationshipType} />
                                </div>
                            </div>

                            <div className="rounded-2xl bg-[color:var(--admin-surface)] px-4 py-4">
                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">Item</p>
                                <p className="mt-3 text-sm font-semibold text-[color:var(--admin-ink)]">{reviewLabel}</p>
                                <p className="mt-1 text-sm leading-6 text-[color:var(--admin-muted)]">{form.saleType === 'ticket' ? `${selectedEvent?.dateLabel} | ${selectedEvent?.venue}` : selectedBookFormat?.fulfillment}</p>
                            </div>

                            <div className="rounded-2xl bg-[color:var(--admin-surface)] px-4 py-4">
                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">Pricing</p>
                                <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">Standard unit price: {formatCurrency(standardUnitPrice)}</p>
                                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">Active unit price: {formatCurrency(activeUnitPrice)}</p>
                                <p className="mt-2 text-lg font-semibold text-[color:var(--admin-ink)]">Total: {formatCurrency(total)}</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    <AdminStatusBadge value={form.paymentMethod} />
                                    <AdminStatusBadge value={form.issueStatus === 'paid' ? 'paid' : 'pending'} />
                                </div>
                            </div>
                        </div>
                    </AdminSectionCard>

                    <AdminSectionCard eyebrow="Latest" icon={ShoppingCart} title="Latest sale">
                        {latestSale ? (
                            <ResultCard
                                deliveryError={latestSaleDeliveryError}
                                feedback={latestSaleDeliveryFeedback}
                                isSendingDelivery={isSendingLatestSale}
                                onSendDelivery={handleSendLatestSale}
                                sale={latestSale}
                            />
                        ) : (
                            <div className="rounded-[1.5rem] border border-dashed border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-6 py-8">
                                <p className="text-lg font-semibold text-[color:var(--admin-ink)]">No sale yet</p>
                                <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">Create a sale to see it here.</p>
                            </div>
                        )}
                    </AdminSectionCard>
                </div>
            </section>
        </div>
    );
}
