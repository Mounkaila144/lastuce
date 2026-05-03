export type EpisodeType = 'episode' | 'coulisse' | 'bonus' | 'special'
export type VideoProvider = 'youtube' | 'facebook'

export interface EpisodeListItem {
  id: number
  slug: string
  titre: string
  type: EpisodeType
  type_label?: string
  description?: string | null
  date_publication?: string | null
  duree?: number | null
  vues?: number
  category?: string | null
  invite_nom?: string | null
  thumbnail_url?: string | null
  video_thumbnail?: string | null
  video_url?: string | null
  video_provider?: VideoProvider | null
  video_embed_url?: string | null
  url?: string
}

export interface BlogArticleListItem {
  id: number
  slug: string
  titre: string
  extrait?: string | null
  image?: string | null
  date_publication?: string | null
  categorie?: string | null
  mots_cles: string[]
  vues: number
  reading_time: number
  url: string
}

export interface BlogArticleShow extends BlogArticleListItem {
  contenu: string
  meta_description?: string | null
}
