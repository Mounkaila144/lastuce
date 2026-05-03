<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import type { SelectOption } from '@/components/ui/Select.vue'

defineOptions({ layout: AdminLayout })

interface UserData {
  id?: number
  name: string
  email: string
  role: string
  permissions?: string[]
}

const props = defineProps<{
  user: UserData | null
  options: { roles: SelectOption[]; permissions: SelectOption[] }
}>()
const isEdit = computed(() => !!props.user?.id)

const form = useForm({
  name: props.user?.name ?? '',
  email: props.user?.email ?? '',
  password: '',
  password_confirmation: '',
  role: props.user?.role ?? 'moderator',
  permissions: props.user?.permissions ?? ([] as string[]),
})

function togglePermission(value: string) {
  const idx = form.permissions.indexOf(value)
  if (idx === -1) form.permissions.push(value)
  else form.permissions.splice(idx, 1)
}

function submit() {
  if (isEdit.value && props.user) {
    form.put(`/admin/users/${props.user.id}`)
  } else {
    form.post('/admin/users')
  }
}
useAdminTitle(() => (isEdit.value ? 'Éditer un admin' : 'Nouvel admin'))
</script>

<template>
  <Head :title="isEdit ? 'Éditer un admin' : 'Nouvel admin'" />

  <form class="space-y-5 max-w-2xl" @submit.prevent="submit">
    <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Identité</h2>
      <Input v-model="form.name" label="Nom complet *" required :error="form.errors.name" />
      <Input v-model="form.email" type="email" label="Email *" required :error="form.errors.email" />
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
        Mot de passe{{ isEdit ? ' (laisser vide pour conserver)' : '' }}
      </h2>
      <Input v-model="form.password" type="password" label="Mot de passe *" :required="!isEdit" :error="form.errors.password" />
      <Input v-model="form.password_confirmation" type="password" label="Confirmer *" :required="!isEdit" :error="form.errors.password_confirmation" />
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Rôle & permissions</h2>
      <Select v-model="form.role" :options="options.roles" label="Rôle *" :error="form.errors.role" />
      <div>
        <p class="mb-2 text-sm font-medium text-slate-700">Permissions spécifiques</p>
        <div class="grid grid-cols-2 gap-2">
          <label
            v-for="perm in options.permissions"
            :key="perm.value"
            class="flex items-center gap-2 cursor-pointer text-sm"
          >
            <input
              type="checkbox"
              :value="perm.value"
              :checked="form.permissions.includes(String(perm.value))"
              class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
              @change="togglePermission(String(perm.value))"
            />
            {{ perm.label }}
          </label>
        </div>
      </div>
    </div>

    <div class="flex gap-3">
      <Button type="submit" variant="primary" :loading="form.processing">
        {{ isEdit ? 'Mettre à jour' : 'Créer' }}
      </Button>
      <Link href="/admin/users" class="inline-flex h-9 items-center rounded-md border border-slate-200 px-4 text-sm font-medium text-slate-600 hover:bg-slate-50">
        Annuler
      </Link>
    </div>
  </form>
</template>
