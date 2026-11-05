<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
})

const page = usePage()
const selectedCode = ref('')

const status = computed(() => page.props.status)
const totalUnits = computed(() => props.products.reduce((sum, product) => sum + product.quantity, 0))
const outOfStock = computed(() => props.products.filter((product) => product.quantity === 0).length)

const addForm = useForm({
    item_code: '',
    quantity: 1,
})

const subtractForm = useForm({
    item_code: '',
    quantity: 1,
})

function selectProduct(itemCode) {
    selectedCode.value = itemCode
    addForm.item_code = itemCode
    subtractForm.item_code = itemCode
}

function submitAdd() {
    addForm.post('/stock/add', {
        preserveScroll: true,
        onSuccess: () => addForm.reset('quantity'),
    })
}

function submitSubtract() {
    subtractForm.post('/stock/subtract', {
        preserveScroll: true,
        onSuccess: () => subtractForm.reset('quantity'),
    })
}

function quantityClass(quantity) {
    if (quantity === 0) {
        return 'text-red-700'
    }

    if (quantity < 10) {
        return 'text-amber-800'
    }

    return 'text-stone-900'
}
</script>

<template>
    <Head title="Stock" />

    <div class="min-h-screen">
        <header class="bg-stone-900 text-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-6 px-5 py-6 sm:px-8 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold">Warehouse stock</h1>
                    <p class="mt-2 max-w-xl text-sm text-stone-300">
                        Current quantities. Click a row to fill the forms.
                    </p>
                </div>
                <dl class="flex gap-8 text-sm">
                    <div>
                        <dt class="text-stone-400">Products</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ products.length }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-400">Units</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ totalUnits }}</dd>
                    </div>
                    <div v-if="outOfStock > 0">
                        <dt class="text-stone-400">Out</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ outOfStock }}</dd>
                    </div>
                </dl>
            </div>
        </header>

        <main class="mx-auto grid max-w-6xl gap-6 px-5 py-6 sm:px-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <p
                v-if="status"
                role="status"
                class="border border-green-200 bg-white px-4 py-3 text-sm text-green-800 lg:col-span-2"
            >
                {{ status }}
            </p>

            <section class="overflow-hidden border border-stone-200 bg-white">
                <div class="flex items-center justify-between border-b border-stone-200 px-4 py-3">
                    <h2 class="text-sm font-semibold">On hand</h2>
                    <p class="text-xs text-stone-500">Click a row to fill the forms</p>
                </div>

                <div v-if="products.length === 0" class="px-4 py-10 text-sm text-stone-500">
                    No products yet. Run <code class="font-mono">php artisan migrate --seed</code>.
                </div>

                <div v-else>
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs tracking-wide text-stone-500 uppercase">
                            <tr class="border-b border-stone-200">
                                <th class="px-4 py-3 font-medium">Item</th>
                                <th class="px-4 py-3 font-medium">Product</th>
                                <th class="hidden px-4 py-3 font-medium md:table-cell">Colour</th>
                                <th class="hidden px-4 py-3 font-medium md:table-cell">Size</th>
                                <th class="px-4 py-3 text-right font-medium">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in products"
                                :key="product.id"
                                class="cursor-pointer border-b border-stone-100 last:border-b-0 hover:bg-stone-50"
                                :class="selectedCode === product.item_code ? 'bg-stone-50' : ''"
                                @click="selectProduct(product.item_code)"
                            >
                                <td class="px-4 py-3 font-mono text-[13px] font-medium whitespace-nowrap">
                                    <button
                                        type="button"
                                        class="inline-flex items-center text-left"
                                        :aria-pressed="selectedCode === product.item_code"
                                        @click.stop="selectProduct(product.item_code)"
                                    >
                                        <span
                                            class="mr-2 inline-block h-2 w-2 rounded-full"
                                            :class="selectedCode === product.item_code ? 'bg-green-700' : 'bg-stone-300'"
                                        />
                                        {{ product.item_code }}
                                    </button>
                                </td>
                                <td class="px-4 py-3">
                                    {{ product.product_name }}
                                    <p class="mt-0.5 text-xs text-stone-500 md:hidden">
                                        {{ product.colour || '—' }} · {{ product.size || '—' }}
                                    </p>
                                </td>
                                <td class="hidden px-4 py-3 text-stone-700 md:table-cell">{{ product.colour || '—' }}</td>
                                <td class="hidden px-4 py-3 text-stone-700 md:table-cell">{{ product.size || '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold whitespace-nowrap tabular-nums" :class="quantityClass(product.quantity)">
                                    {{ product.quantity }}
                                    <span v-if="product.quantity === 0" class="ml-1 text-xs font-medium uppercase">Out</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="space-y-4">
                <form class="border border-stone-200 bg-white p-4" @submit.prevent="submitAdd">
                    <h2 class="text-sm font-semibold">Add stock</h2>
                    <p class="mt-1 text-xs text-stone-500">Adds to the current quantity.</p>

                    <label class="mt-4 block">
                        <span class="field-label">Item code</span>
                        <input
                            v-model="addForm.item_code"
                            list="item-codes"
                            class="field font-mono"
                            autocomplete="off"
                            required
                        >
                    </label>
                    <p v-if="addForm.errors.item_code" class="mt-1 text-xs text-red-700">{{ addForm.errors.item_code }}</p>

                    <label class="mt-3 block">
                        <span class="field-label">Quantity</span>
                        <input v-model.number="addForm.quantity" type="number" min="1" step="1" class="field tabular-nums" required>
                    </label>
                    <p v-if="addForm.errors.quantity" class="mt-1 text-xs text-red-700">{{ addForm.errors.quantity }}</p>

                    <button
                        type="submit"
                        class="mt-4 w-full rounded bg-green-800 px-3 py-2 text-sm font-medium text-white hover:bg-green-900 disabled:opacity-60"
                        :disabled="addForm.processing"
                    >
                        {{ addForm.processing ? 'Saving…' : 'Add to stock' }}
                    </button>
                </form>

                <form class="border border-stone-200 bg-white p-4" @submit.prevent="submitSubtract">
                    <h2 class="text-sm font-semibold">Subtract stock</h2>
                    <p class="mt-1 text-xs text-stone-500">Will not go below zero.</p>

                    <label class="mt-4 block">
                        <span class="field-label">Item code</span>
                        <input
                            v-model="subtractForm.item_code"
                            list="item-codes"
                            class="field font-mono"
                            autocomplete="off"
                            required
                        >
                    </label>
                    <p v-if="subtractForm.errors.item_code" class="mt-1 text-xs text-red-700">{{ subtractForm.errors.item_code }}</p>

                    <label class="mt-3 block">
                        <span class="field-label">Quantity</span>
                        <input v-model.number="subtractForm.quantity" type="number" min="1" step="1" class="field tabular-nums" required>
                    </label>
                    <p v-if="subtractForm.errors.quantity" class="mt-1 text-xs text-red-700">{{ subtractForm.errors.quantity }}</p>

                    <button
                        type="submit"
                        class="mt-4 w-full rounded bg-red-800 px-3 py-2 text-sm font-medium text-white hover:bg-red-900 disabled:opacity-60"
                        :disabled="subtractForm.processing"
                    >
                        {{ subtractForm.processing ? 'Saving…' : 'Subtract from stock' }}
                    </button>
                </form>
            </aside>
        </main>

        <datalist id="item-codes">
            <option
                v-for="product in products"
                :key="product.id"
                :value="product.item_code"
                :label="`${product.product_name}, ${product.colour || ''} ${product.size || ''}`"
            />
        </datalist>
    </div>
</template>
