<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import { useToast } from '@/composables/useToast'
import { useUiStore } from '@/stores/ui'

const props = withDefaults(
  defineProps<{
    /**
     * Endpoint d'inscription. Si omis, on cible la route locale-aware
     * `/${locale}/newsletter/quick-subscribe` (story S5.3).
     */
    endpoint?: string | null
    /** Source taguée côté serveur (footer, hero, modal…). */
    source?: string
    variant?: 'inline' | 'stacked'
  }>(),
  {
    endpoint: null,
    source: 'inline',
    variant: 'inline',
  },
)

const ui = useUiStore()
const resolvedEndpoint = computed(() => props.endpoint ?? `/${ui.locale}/newsletter/quick-subscribe`)

type Status = 'idle' | 'loading' | 'success' | 'error'

const email = ref('')
const status = ref<Status>('idle')
const errorMessage = ref('')
const toast = useToast()

function submit() {
  if (status.value === 'loading') return
  errorMessage.value = ''
  status.value = 'loading'

  router.post(
    resolvedEndpoint.value,
    { email: email.value, source: props.source, website: '' },
    {
      preserveScroll: true,
      onSuccess: () => {
        status.value = 'success'
        toast.success('Vérifiez votre boîte mail pour confirmer votre inscription.', {
          title: 'Inscription envoyée',
        })
        email.value = ''
      },
      onError: (errors) => {
        status.value = 'error'
        errorMessage.value =
          (errors.email as string | undefined) ?? 'Une erreur est survenue, réessayez.'
      },
      onFinish: () => {
        if (status.value === 'loading') status.value = 'idle'
      },
    },
  )
}
</script>

<template>
  <form
    :class="['flex w-full gap-2', variant === 'stacked' ? 'flex-col' : 'sm:flex-row flex-col']"
    novalidate
    @submit.prevent="submit"
  >
    <div class="flex-1">
      <Input
        v-model="email"
        type="email"
        name="email"
        autocomplete="email"
        required
        :placeholder="$t('newsletter.placeholder')"
        :error="status === 'error' ? errorMessage : undefined"
        :aria-label="$t('newsletter.placeholder')"
      />
    </div>
    <Button
      type="submit"
      :loading="status === 'loading'"
      :disabled="status === 'success'"
      :variant="status === 'success' ? 'secondary' : 'primary'"
      :size="variant === 'stacked' ? 'lg' : 'md'"
    >
      <span v-if="status === 'success'">✓</span>
      <span v-else>{{ $t('newsletter.submit') }}</span>
    </Button>
  </form>
</template>
