<template>
  <AdminLayout>
    <div class="p-6 space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Package class="w-6 h-6 text-indigo-400" />
          <h1 class="text-xl font-semibold text-slate-800">Add-ons Management</h1>
        </div>
        <button @click="showCreate = true"
          class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
          <Plus class="w-4 h-4" /> New Add-on
        </button>
      </div>

      <!-- Flash -->
      <div v-if="$page.props.flash?.success"
        class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
        <CheckCircle class="w-4 h-4" /> {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.errors?.error"
        class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
        <AlertCircle class="w-4 h-4" /> {{ $page.props.errors.error }}
      </div>

      <!-- Add-ons Table -->
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
          <Package class="w-4 h-4 text-slate-400" />
          <span class="font-medium text-slate-700">All Add-ons</span>
        </div>

        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
            <tr>
              <th class="px-5 py-3 text-left">Add-on</th>
              <th class="px-5 py-3 text-left">Price</th>
              <th class="px-5 py-3 text-left">Active</th>
              <th class="px-5 py-3 text-left">Total Sales</th>
              <th class="px-5 py-3 text-left">Status</th>
              <th class="px-5 py-3 text-left">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="addon in addons" :key="addon.id" class="hover:bg-slate-50">
              <td class="px-5 py-3">
                <p class="font-medium text-slate-800">{{ addon.name }}</p>
                <p class="text-xs text-slate-400 font-mono">{{ addon.slug }}</p>
              </td>
              <td class="px-5 py-3 text-slate-700">
                {{ addon.currency }} {{ addon.price }}<span class="text-slate-400 text-xs">/{{ addon.billing_cycle }}</span>
              </td>
              <td class="px-5 py-3">
                <span class="font-semibold text-indigo-600">{{ addon.active_count }}</span>
              </td>
              <td class="px-5 py-3 text-slate-600">{{ addon.total_purchases }}</td>
              <td class="px-5 py-3">
                <span :class="addon.is_active
                  ? 'bg-green-100 text-green-700'
                  : 'bg-slate-100 text-slate-500'"
                  class="px-2 py-0.5 rounded-full text-xs font-medium">
                  {{ addon.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-2">
                  <button @click="openEdit(addon)" class="text-slate-400 hover:text-indigo-600 transition-colors">
                    <Edit2 class="w-4 h-4" />
                  </button>
                  <button @click="confirmDelete(addon)" class="text-slate-400 hover:text-red-500 transition-colors">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!addons.length">
              <td colspan="6" class="px-5 py-10 text-center text-slate-400">
                <Package class="w-8 h-8 mx-auto mb-2 opacity-30" />
                No add-ons yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pending Approvals -->
      <div v-if="pending_payments.length" class="bg-white rounded-xl border border-amber-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-amber-100 flex items-center gap-2 bg-amber-50">
          <Clock class="w-4 h-4 text-amber-500" />
          <span class="font-medium text-amber-700">Pending Add-on Purchases ({{ pending_payments.length }})</span>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
            <tr>
              <th class="px-5 py-3 text-left">Tenant</th>
              <th class="px-5 py-3 text-left">Add-on</th>
              <th class="px-5 py-3 text-left">Amount</th>
              <th class="px-5 py-3 text-left">Method</th>
              <th class="px-5 py-3 text-left">Date</th>
              <th class="px-5 py-3 text-left">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="p in pending_payments" :key="p.id" class="hover:bg-slate-50">
              <td class="px-5 py-3 font-medium text-slate-800">{{ p.tenant?.name }}</td>
              <td class="px-5 py-3 text-slate-600">{{ p.addon?.name }}</td>
              <td class="px-5 py-3 text-slate-700">{{ p.currency }} {{ p.amount }}</td>
              <td class="px-5 py-3 uppercase text-xs font-medium text-slate-500">{{ p.method }}</td>
              <td class="px-5 py-3 text-slate-400 text-xs">{{ new Date(p.created_at).toLocaleDateString() }}</td>
              <td class="px-5 py-3">
                <button @click="approve(p.id)"
                  class="flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                  <CheckCircle class="w-3.5 h-3.5" /> Approve
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- Create Modal -->
    <div v-if="showCreate" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl w-full max-w-lg p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-slate-800 flex items-center gap-2"><Plus class="w-4 h-4" /> New Add-on</h2>
          <button @click="showCreate = false"><X class="w-5 h-5 text-slate-400" /></button>
        </div>
        <AddonForm :form="createForm" @submit="submitCreate" @cancel="showCreate = false" />
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="editAddon" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl w-full max-w-lg p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-slate-800 flex items-center gap-2"><Edit2 class="w-4 h-4" /> Edit Add-on</h2>
          <button @click="editAddon = null"><X class="w-5 h-5 text-slate-400" /></button>
        </div>
        <AddonForm :form="editForm" :is-edit="true" @submit="submitEdit" @cancel="editAddon = null" />
      </div>
    </div>

  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Package, Plus, Edit2, Trash2, CheckCircle, AlertCircle, Clock, X } from 'lucide-vue-next'

const props = defineProps({
  addons:           { type: Array, default: () => [] },
  pending_payments: { type: Array, default: () => [] },
})

const showCreate = ref(false)
const editAddon  = ref(null)

const blankForm = () => ({
  name: '', slug: '', description: '', price: '',
  currency: 'USD', billing_cycle: 'monthly',
  features: [], is_active: true, sort_order: 0,
})

const createForm = ref(blankForm())
const editForm   = ref(blankForm())

function openEdit(addon) {
  editAddon.value = addon
  editForm.value  = {
    name: addon.name, slug: addon.slug, description: addon.description ?? '',
    price: addon.price, currency: addon.currency,
    billing_cycle: addon.billing_cycle,
    features: addon.features ?? [], is_active: addon.is_active,
    sort_order: addon.sort_order,
  }
}

function submitCreate() {
  router.post(route('admin.addons.store'), createForm.value, {
    preserveScroll: true,
    onSuccess: () => { showCreate.value = false; createForm.value = blankForm() },
  })
}

function submitEdit() {
  router.put(route('admin.addons.update', editAddon.value.id), editForm.value, {
    preserveScroll: true,
    onSuccess: () => { editAddon.value = null },
  })
}

function confirmDelete(addon) {
  if (confirm(`Delete "${addon.name}"? This cannot be undone.`)) {
    router.delete(route('admin.addons.destroy', addon.id), { preserveScroll: true })
  }
}

function approve(paymentId) {
  router.put(route('admin.addons.approve', paymentId), {}, { preserveScroll: true })
}
</script>

<!-- Inline sub-component for the form -->
<script>
import { defineComponent, h, ref, watch } from 'vue'

const AddonForm = defineComponent({
  name: 'AddonForm',
  props: {
    form:   { type: Object, required: true },
    isEdit: { type: Boolean, default: false },
  },
  emits: ['submit', 'cancel'],
  setup(props, { emit }) {
    const featureInput = ref('')

    function addFeature() {
      if (featureInput.value.trim()) {
        props.form.features.push(featureInput.value.trim())
        featureInput.value = ''
      }
    }

    function removeFeature(i) { props.form.features.splice(i, 1) }

    const field = (label, slot) => h('div', { class: 'space-y-1' }, [
      h('label', { class: 'text-xs font-medium text-slate-600' }, label),
      slot,
    ])

    const input = (modelKey, type = 'text', extra = {}) =>
      h('input', {
        type, value: props.form[modelKey],
        onInput: e => props.form[modelKey] = type === 'number' ? parseFloat(e.target.value) : e.target.value,
        class: 'w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400',
        ...extra,
      })

    return () => h('div', { class: 'space-y-4' }, [
      field('Name', input('name')),
      !props.isEdit ? field('Slug (e.g. agent-ai)', input('slug')) : null,
      field('Description', h('textarea', {
        value: props.form.description,
        onInput: e => props.form.description = e.target.value,
        rows: 2,
        class: 'w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400 resize-none',
      })),
      h('div', { class: 'grid grid-cols-3 gap-3' }, [
        field('Price', input('price', 'number', { step: '0.01', min: '0' })),
        field('Currency', h('select', {
          value: props.form.currency,
          onChange: e => props.form.currency = e.target.value,
          class: 'w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400',
        }, ['USD','BDT','EUR'].map(c => h('option', { value: c }, c)))),
        field('Billing', h('select', {
          value: props.form.billing_cycle,
          onChange: e => props.form.billing_cycle = e.target.value,
          class: 'w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400',
        }, ['monthly','yearly','one_time'].map(c => h('option', { value: c }, c)))),
      ]),
      field('Features', h('div', { class: 'space-y-2' }, [
        h('div', { class: 'flex gap-2' }, [
          h('input', {
            type: 'text', value: featureInput.value,
            onInput: e => featureInput.value = e.target.value,
            onKeydown: e => e.key === 'Enter' && (e.preventDefault(), addFeature()),
            placeholder: 'Add feature, press Enter',
            class: 'flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400',
          }),
          h('button', { type: 'button', onClick: addFeature, class: 'px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm' }, '+'),
        ]),
        props.form.features.length
          ? h('div', { class: 'flex flex-wrap gap-1' }, props.form.features.map((f, i) =>
              h('span', { key: i, class: 'flex items-center gap-1 bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-xs' }, [
                f,
                h('button', { type: 'button', onClick: () => removeFeature(i), class: 'ml-1 text-indigo-400 hover:text-indigo-700' }, '×'),
              ])))
          : null,
      ])),
      props.isEdit ? field('Status', h('label', { class: 'flex items-center gap-2 text-sm' }, [
        h('input', {
          type: 'checkbox', checked: props.form.is_active,
          onChange: e => props.form.is_active = e.target.checked,
          class: 'rounded',
        }),
        'Active',
      ])) : null,
      h('div', { class: 'flex justify-end gap-2 pt-2' }, [
        h('button', { type: 'button', onClick: () => emit('cancel'), class: 'px-4 py-2 text-sm text-slate-500 hover:text-slate-700' }, 'Cancel'),
        h('button', { type: 'button', onClick: () => emit('submit'), class: 'px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg font-medium' },
          props.isEdit ? 'Save Changes' : 'Create Add-on'),
      ]),
    ])
  },
})

export default { components: { AddonForm } }
</script>