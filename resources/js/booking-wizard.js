export default function bookingWizard(config) {
    return {
        barbers: config.barbers,
        availabilityUrl: config.availabilityUrl,
        holdUrl: config.holdUrl,
        csrf: config.csrf,
        step: 1,
        progress: [
            { step: 1, label: 'آرایشگر' },
            { step: 2, label: 'خدمات' },
            { step: 3, label: 'زمان' },
            { step: 4, label: 'تأیید' },
        ],
        dates: [],
        selectedBarberId: null,
        selectedServiceIds: [],
        selectedDate: null,
        selectedSlot: null,
        slots: [],
        quote: null,
        durationMinutes: 0,
        loadingSlots: false,
        submitting: false,
        error: '',
        customer: {
            full_name: '',
            mobile: '',
            notes: '',
        },

        get selectedBarber() {
            return this.barbers.find((barber) => barber.id === this.selectedBarberId) ?? null;
        },

        get availableServices() {
            return this.selectedBarber?.services ?? [];
        },

        init() {
            const now = new Date();

            this.dates = Array.from({ length: 14 }, (_, offset) => {
                const date = new Date(now.getTime() + offset * 86_400_000);
                const value = this.tehranDateKey(date);

                return {
                    value,
                    weekday: new Intl.DateTimeFormat('fa-IR-u-ca-persian', { weekday: 'short', timeZone: 'Asia/Tehran' }).format(date),
                    day: new Intl.DateTimeFormat('fa-IR-u-ca-persian', { day: 'numeric', timeZone: 'Asia/Tehran' }).format(date),
                    month: new Intl.DateTimeFormat('fa-IR-u-ca-persian', { month: 'short', timeZone: 'Asia/Tehran' }).format(date),
                };
            });
        },

        tehranDateKey(date) {
            const parts = new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                timeZone: 'Asia/Tehran',
            }).formatToParts(date);
            const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));

            return `${values.year}-${values.month}-${values.day}`;
        },

        selectBarber(barberId) {
            if (this.selectedBarberId !== barberId) {
                this.selectedBarberId = barberId;
                this.selectedServiceIds = [];
                this.selectedDate = null;
                this.selectedSlot = null;
                this.slots = [];
                this.quote = null;
            }
        },

        toggleService(serviceId) {
            this.selectedServiceIds = this.selectedServiceIds.includes(serviceId)
                ? this.selectedServiceIds.filter((id) => id !== serviceId)
                : [...this.selectedServiceIds, serviceId];

            if (this.selectedDate) {
                this.fetchAvailability();
            }
        },

        chooseDate(date) {
            this.selectedDate = date;
            this.selectedSlot = null;
            this.fetchAvailability();
        },

        async fetchAvailability() {
            this.loadingSlots = true;
            this.error = '';
            this.slots = [];
            this.selectedSlot = null;

            const query = new URLSearchParams({
                barber_id: this.selectedBarberId,
                date: this.selectedDate,
            });
            this.selectedServiceIds.forEach((id) => query.append('service_ids[]', id));

            try {
                const response = await fetch(`${this.availabilityUrl}?${query.toString()}`, {
                    headers: { Accept: 'application/json' },
                });
                const body = await response.json();

                if (!response.ok) {
                    throw new Error(this.responseMessage(body));
                }

                this.slots = body.data.slots;
                this.quote = body.data.quote;
                this.durationMinutes = body.data.duration_minutes;
            } catch (error) {
                this.error = error.message || 'دریافت ساعت‌های آزاد ممکن نشد. دوباره تلاش کنید.';
            } finally {
                this.loadingSlots = false;
            }
        },

        async submitHold() {
            if (this.submitting || !this.selectedSlot || !this.customer.full_name || !this.customer.mobile) {
                return;
            }

            this.submitting = true;
            this.error = '';

            try {
                const response = await fetch(this.holdUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        barber_id: this.selectedBarberId,
                        starts_at: this.selectedSlot,
                        service_ids: this.selectedServiceIds,
                        full_name: this.customer.full_name,
                        mobile: this.customer.mobile,
                        notes: this.customer.notes || null,
                    }),
                });
                const body = await response.json();

                if (!response.ok) {
                    if (response.status === 409) {
                        this.step = 3;
                        await this.fetchAvailability();
                    }

                    throw new Error(this.responseMessage(body));
                }

                window.location.assign(body.data.redirect_url);
            } catch (error) {
                this.error = error.message || 'ثبت رزرو ممکن نشد. دوباره تلاش کنید.';
            } finally {
                this.submitting = false;
            }
        },

        responseMessage(body) {
            const validationMessage = body.errors ? Object.values(body.errors).flat()[0] : null;

            return validationMessage || body.message || 'خطایی رخ داد. دوباره تلاش کنید.';
        },

        money(amount) {
            return `${new Intl.NumberFormat('fa-IR').format(amount)} ریال`;
        },

        time(value) {
            if (!value) {
                return '—';
            }

            return new Intl.DateTimeFormat('fa-IR', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
                timeZone: 'Asia/Tehran',
            }).format(new Date(value));
        },

        longDate(value) {
            if (!value) {
                return '—';
            }

            return new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                timeZone: 'Asia/Tehran',
            }).format(new Date(`${value}T12:00:00+03:30`));
        },
    };
}
