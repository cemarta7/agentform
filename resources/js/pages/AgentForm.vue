<template>
  <div class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a] flex items-center justify-center p-6">
    <div class="max-w-md w-full p-6 bg-white rounded shadow dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] border border-neutral-200 dark:border-[#3E3E3A]">
      <h1 class="text-2xl font-bold mb-4">Agent Form</h1>
      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block mb-1 font-semibold" for="name">Name</label>
          <input v-model="form.name" id="name" type="text" class="w-full border rounded px-3 py-2 bg-white text-black dark:bg-[#161615] dark:text-[#EDEDEC] dark:border-[#3E3E3A]" required />
          <div v-if="form.errors.name" class="text-red-500 text-sm mt-1 dark:text-red-400">{{ form.errors.name }}</div>
        </div>
        <div class="mb-4">
          <label class="block mb-1 font-semibold" for="email">Email</label>
          <input v-model="form.email" id="email" type="email" class="w-full border rounded px-3 py-2 bg-white text-black dark:bg-[#161615] dark:text-[#EDEDEC] dark:border-[#3E3E3A]" required />
          <div v-if="form.errors.email" class="text-red-500 text-sm mt-1 dark:text-red-400">{{ form.errors.email }}</div>
        </div>
        <div class="mb-4">
          <label class="block mb-1 font-semibold" for="secret">Secret</label>
          <input v-model="form.secret" id="secret" type="text" class="w-full border rounded px-3 py-2 bg-white text-black dark:bg-[#161615] dark:text-[#EDEDEC] dark:border-[#3E3E3A]" required />
          <div v-if="form.errors.secret" class="text-red-500 text-sm mt-1 dark:text-red-400">{{ form.errors.secret }}</div>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">Submit</button>
      </form>
      <div v-if="flashSuccess" class="mt-4 text-green-600 font-semibold dark:text-green-400">
        {{ flashSuccess }}
      </div>
      <div v-if="flashError" class="mt-4 text-red-600 font-semibold dark:text-red-400">
        {{ flashError }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { computed } from 'vue'

const form = useForm({
  name: '',
  email: '',
  secret: '',
})

const page = usePage();
const flashSuccess = computed(() => {
  return (page.props.flash as { success?: string } | undefined)?.success || ''
})

const flashError = computed(() => {
  return (page.props.flash as { error?: string } | undefined)?.error || ''
})

function submit() {
  form.post(route('agentform.store'), {
    onSuccess: () => {
      form.reset()
    }
  })
}
</script>
