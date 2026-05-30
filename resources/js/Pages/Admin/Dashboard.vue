<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Sparkline from '@/components/domain/Sparkline.vue'

defineOptions({ layout: AdminLayout })

interface SectionStat {
  total: number
  this_month?: number
  this_week?: number
  [key: string]: number | undefined
}

interface Stats {
  episodes: SectionStat & { published: number; draft: number }
  astuces: SectionStat & { pending: number; approved: number; rejected: number }
  partenariats: SectionStat & { pending: number; approved: number; rejected: number }
  newsletter: SectionStat & { active: number; pending: number; unsubscribed: number }
  blog: SectionStat & { published: number; draft: number }
}

interface ActivityItem {
  id: number
  user: string
  action: string
  description: string
  severity: string
  severity_color: string
  created_at: string
  model_type?: string | null
}

interface SecurityStats {
  failed_logins_today: number
  failed_logins_week: number
  blocked_ips: number
  admin_logins_today: number
  critical_actions_week: number
}

interface ChartData {
  labels: string[]
  episodes: number[]
  astuces: number[]
  newsletter: number[]
}

interface AlertItem {
  type: 'warning' | 'info' | 'danger' | string
  icon: string
  title: string
  message: string
  action_url: string
  action_text: string
}

defineProps<{
  stats: Stats
  recentActivity: ActivityItem[]
  securityStats: SecurityStats
  chartData: ChartData
  alerts: AlertItem[]
}>()

const alertClass: Record<string, string> = {
  warning: 'border-amber-200 bg-amber-50 text-amber-900',
  info: 'border-sky-200 bg-sky-50 text-sky-900',
  danger: 'border-red-200 bg-red-50 text-red-900',
}

const severityClass: Record<string, string> = {
  info: 'bg-sky-100 text-sky-800',
  warning: 'bg-amber-100 text-amber-800',
  critical: 'bg-red-100 text-red-800',
  error: 'bg-red-100 text-red-800',
}
useAdminTitle('Tableau de bord')
</script>

<template>
  <Head title="Tableau de bord" />


  <div class="space-y-6">
    <!-- Cartes stats principales -->
    <section
      class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
      aria-label="Statistiques principales"
    >
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Épisodes publiés</p>
        <p class="mt-2 text-3xl font-bold text-slate-900">
          {{ stats.episodes.published.toLocaleString('fr-FR') }}
        </p>
        <p class="mt-1 text-xs text-slate-500">
          Total : {{ stats.episodes.total }} · Brouillons : {{ stats.episodes.draft }}
        </p>
        <Link
          href="/admin/episodes"
          class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800"
        >
          Gérer →
        </Link>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
          Astuces en attente
        </p>
        <p class="mt-2 text-3xl font-bold text-slate-900">
          {{ stats.astuces.pending.toLocaleString('fr-FR') }}
        </p>
        <p class="mt-1 text-xs text-slate-500">
          Approuvées : {{ stats.astuces.approved }} · Rejetées : {{ stats.astuces.rejected }}
        </p>
        <Link
          href="/admin/astuces?status=en_attente"
          class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800"
        >
          Modérer →
        </Link>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
          Abonnés newsletter
        </p>
        <p class="mt-2 text-3xl font-bold text-slate-900">
          {{ stats.newsletter.active.toLocaleString('fr-FR') }}
        </p>
        <p class="mt-1 text-xs text-slate-500">
          Total : {{ stats.newsletter.total }} · En attente : {{ stats.newsletter.pending }}
        </p>
        <Link
          href="/admin/newsletter"
          class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800"
        >
          Voir →
        </Link>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
          Partenariats en cours
        </p>
        <p class="mt-2 text-3xl font-bold text-slate-900">
          {{ stats.partenariats.pending.toLocaleString('fr-FR') }}
        </p>
        <p class="mt-1 text-xs text-slate-500">
          Acceptés : {{ stats.partenariats.approved }} · Refusés :
          {{ stats.partenariats.rejected }}
        </p>
        <Link
          href="/admin/partenariats?status=en_attente"
          class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800"
        >
          Examiner →
        </Link>
      </div>
    </section>

    <!-- Action rapide -->
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-base font-semibold text-slate-900">Actions rapides</h2>
          <p class="text-xs text-slate-500">Raccourcis vers les opérations courantes.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link
            href="/admin/episodes/create"
            class="inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
          >
            + Nouvel épisode
          </Link>
          <Link
            href="/admin/blog/create"
            class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            + Nouvel article
          </Link>
        </div>
      </div>
    </section>

    <!-- Alertes -->
    <section v-if="alerts.length" class="space-y-3" aria-label="Alertes">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
        Alertes
      </h2>
      <ul class="space-y-2">
        <li
          v-for="(alert, i) in alerts"
          :key="i"
          :class="['flex flex-wrap items-start justify-between gap-3 rounded-lg border p-3 text-sm', alertClass[alert.type] ?? alertClass.info]"
        >
          <div>
            <p class="font-semibold">{{ alert.title }}</p>
            <p class="text-xs">{{ alert.message }}</p>
          </div>
          <a
            :href="alert.action_url"
            class="text-xs font-semibold underline underline-offset-2 hover:no-underline"
          >
            {{ alert.action_text }}
          </a>
        </li>
      </ul>
    </section>

    <!-- Graphiques 30 jours -->
    <section class="grid gap-4 lg:grid-cols-2" aria-label="Graphiques 30 jours">
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <Sparkline
          :values="chartData.episodes"
          :labels="chartData.labels"
          label="Épisodes créés (30j)"
          stroke="#272757"
          fill="rgba(39,39,87,0.12)"
        />
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <Sparkline
          :values="chartData.astuces"
          :labels="chartData.labels"
          label="Astuces soumises (30j)"
          stroke="#f59e0b"
          fill="rgba(245,158,11,0.12)"
        />
      </div>
    </section>

    <!-- Activité + sécurité -->
    <section class="grid gap-4 lg:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
          Dernière activité
        </h2>
        <ul v-if="recentActivity.length" class="divide-y divide-slate-100">
          <li
            v-for="entry in recentActivity"
            :key="entry.id"
            class="flex items-start gap-3 py-2.5 text-sm"
          >
            <span
              :class="['shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold capitalize', severityClass[entry.severity] ?? 'bg-slate-100 text-slate-700']"
            >
              {{ entry.severity }}
            </span>
            <div class="flex-1">
              <p class="text-slate-800">{{ entry.description }}</p>
              <p class="text-xs text-slate-500">
                {{ entry.user }} · {{ entry.action }} · {{ entry.created_at }}
              </p>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-500">Aucune activité enregistrée pour l'instant.</p>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Sécurité</h2>
        <dl class="space-y-2 text-sm">
          <div class="flex items-center justify-between">
            <dt class="text-slate-600">Échecs de connexion (24h)</dt>
            <dd class="font-semibold text-slate-900">
              {{ securityStats.failed_logins_today.toLocaleString('fr-FR') }}
            </dd>
          </div>
          <div class="flex items-center justify-between">
            <dt class="text-slate-600">Échecs (7j)</dt>
            <dd class="font-semibold text-slate-900">
              {{ securityStats.failed_logins_week.toLocaleString('fr-FR') }}
            </dd>
          </div>
          <div class="flex items-center justify-between">
            <dt class="text-slate-600">IP suspectes (1h)</dt>
            <dd class="font-semibold text-slate-900">
              {{ securityStats.blocked_ips.toLocaleString('fr-FR') }}
            </dd>
          </div>
          <div class="flex items-center justify-between">
            <dt class="text-slate-600">Connexions admin (24h)</dt>
            <dd class="font-semibold text-slate-900">
              {{ securityStats.admin_logins_today.toLocaleString('fr-FR') }}
            </dd>
          </div>
          <div class="flex items-center justify-between">
            <dt class="text-slate-600">Actions critiques (7j)</dt>
            <dd class="font-semibold text-slate-900">
              {{ securityStats.critical_actions_week.toLocaleString('fr-FR') }}
            </dd>
          </div>
        </dl>
        <Link
          href="/admin/security/logs"
          class="mt-4 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800"
        >
          Voir tous les logs →
        </Link>
      </div>
    </section>
  </div>
</template>
