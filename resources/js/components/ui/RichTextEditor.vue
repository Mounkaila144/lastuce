<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'

const props = withDefaults(
  defineProps<{
    modelValue?: string | null
    label?: string
    placeholder?: string
    error?: string
    disabled?: boolean
    minHeight?: string
  }>(),
  {
    modelValue: '',
    minHeight: '12rem',
  },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const editor = useEditor({
  content: props.modelValue ?? '',
  editable: !props.disabled,
  extensions: [
    StarterKit.configure({
      // Link extension est ajouté séparément ci-dessous pour activer
      // openOnClick=false (pour ne pas suivre le lien quand on édite).
    }),
    Link.configure({ openOnClick: false, autolink: true }),
    Image.configure({ inline: false, allowBase64: true }),
  ],
  editorProps: {
    attributes: {
      class:
        'prose prose-sm max-w-none focus:outline-none px-3 py-2 min-h-[var(--rte-min-height)]',
    },
  },
  onUpdate({ editor }) {
    emit('update:modelValue', editor.getHTML())
  },
})

watch(
  () => props.modelValue,
  (value) => {
    if (!editor.value) return
    if (editor.value.getHTML() === (value ?? '')) return
    editor.value.commands.setContent(value ?? '', { emitUpdate: false })
  },
)

watch(
  () => props.disabled,
  (disabled) => {
    if (!editor.value) return
    editor.value.setEditable(!disabled)
  },
)

onBeforeUnmount(() => {
  editor.value?.destroy()
})

function setLink() {
  if (!editor.value) return
  const previous = editor.value.getAttributes('link').href ?? ''
  const url = window.prompt('URL du lien', previous)
  if (url === null) return
  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }
  editor.value
    .chain()
    .focus()
    .extendMarkRange('link')
    .setLink({ href: url, target: '_blank' })
    .run()
}

function isActive(name: string, attrs: Record<string, unknown> = {}): boolean {
  return editor.value?.isActive(name, attrs) ?? false
}
</script>

<template>
  <div class="space-y-1" :style="{ '--rte-min-height': minHeight }">
    <label v-if="label" class="block text-sm font-medium text-slate-700">
      {{ label }}
    </label>

    <div
      :class="[
        'overflow-hidden rounded-lg border bg-white',
        error ? 'border-red-500' : 'border-slate-200',
      ]"
    >
      <div
        v-if="editor"
        class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-xs"
        role="toolbar"
        aria-label="Mise en forme"
      >
        <button
          type="button"
          class="rounded px-2 py-1 font-bold hover:bg-slate-200"
          :class="{ 'bg-slate-200': isActive('bold') }"
          @click="editor.chain().focus().toggleBold().run()"
        >
          B
        </button>
        <button
          type="button"
          class="rounded px-2 py-1 italic hover:bg-slate-200"
          :class="{ 'bg-slate-200': isActive('italic') }"
          @click="editor.chain().focus().toggleItalic().run()"
        >
          I
        </button>
        <span class="mx-1 h-4 w-px bg-slate-300" aria-hidden="true" />
        <button
          type="button"
          class="rounded px-2 py-1 hover:bg-slate-200"
          :class="{ 'bg-slate-200': isActive('heading', { level: 2 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        >
          H2
        </button>
        <button
          type="button"
          class="rounded px-2 py-1 hover:bg-slate-200"
          :class="{ 'bg-slate-200': isActive('heading', { level: 3 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        >
          H3
        </button>
        <span class="mx-1 h-4 w-px bg-slate-300" aria-hidden="true" />
        <button
          type="button"
          class="rounded px-2 py-1 hover:bg-slate-200"
          :class="{ 'bg-slate-200': isActive('bulletList') }"
          @click="editor.chain().focus().toggleBulletList().run()"
        >
          • Liste
        </button>
        <button
          type="button"
          class="rounded px-2 py-1 hover:bg-slate-200"
          :class="{ 'bg-slate-200': isActive('orderedList') }"
          @click="editor.chain().focus().toggleOrderedList().run()"
        >
          1. Liste
        </button>
        <button
          type="button"
          class="rounded px-2 py-1 hover:bg-slate-200"
          :class="{ 'bg-slate-200': isActive('blockquote') }"
          @click="editor.chain().focus().toggleBlockquote().run()"
        >
          ❝
        </button>
        <span class="mx-1 h-4 w-px bg-slate-300" aria-hidden="true" />
        <button
          type="button"
          class="rounded px-2 py-1 hover:bg-slate-200"
          :class="{ 'bg-slate-200': isActive('link') }"
          @click="setLink"
        >
          Lien
        </button>
        <button
          type="button"
          class="rounded px-2 py-1 hover:bg-slate-200"
          @click="editor.chain().focus().undo().run()"
          :disabled="!editor.can().undo()"
        >
          ↶
        </button>
        <button
          type="button"
          class="rounded px-2 py-1 hover:bg-slate-200"
          @click="editor.chain().focus().redo().run()"
          :disabled="!editor.can().redo()"
        >
          ↷
        </button>
      </div>
      <EditorContent :editor="editor" />
    </div>
    <p v-if="error" class="text-xs text-red-600" role="alert">{{ error }}</p>
    <p
      v-else-if="placeholder && !modelValue"
      class="text-xs text-slate-500"
    >
      {{ placeholder }}
    </p>
  </div>
</template>
