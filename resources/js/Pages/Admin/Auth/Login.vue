<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import BlankLayout from '@/Layouts/BlankLayout.vue'

defineOptions({ layout: BlankLayout })

defineProps<{
  status?: string | null
}>()

const form = useForm({
  email: '',
  password: '',
  remember: false as boolean,
})

function submit() {
  form.post('/admin/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Connexion administration" />

  <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-12">
    <div class="w-full max-w-md">
      <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-slate-900">L'Astuce — Administration</h1>
        <p class="mt-1 text-sm text-slate-600">Accès réservé.</p>
      </div>

      <form
        class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        @submit.prevent="submit"
      >
        <div
          v-if="status"
          class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800"
          role="status"
        >
          {{ status }}
        </div>

        <Input
          v-model="form.email"
          type="email"
          label="Adresse email"
          placeholder="vous@lastuce.tld"
          autocomplete="email"
          required
          :error="form.errors.email"
        />

        <Input
          v-model="form.password"
          type="password"
          label="Mot de passe"
          autocomplete="current-password"
          required
          :error="form.errors.password"
        />

        <div class="flex items-center justify-between">
          <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input
              v-model="form.remember"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
            />
            Se souvenir de moi
          </label>
          <Link
            href="/admin/forgot-password"
            class="text-sm font-medium text-brand-700 hover:text-brand-800"
          >
            Mot de passe oublié ?
          </Link>
        </div>

        <Button type="submit" variant="primary" size="md" :loading="form.processing" class="w-full">
          Se connecter
        </Button>
      </form>

      <p class="mt-4 text-center text-xs text-slate-500">
        L'Astuce ©
        <span>{{ new Date().getFullYear() }}</span>
      </p>
    </div>
  </div>
</template>
