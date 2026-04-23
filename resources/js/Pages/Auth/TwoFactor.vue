<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-icon">🔐</div>
      <h1 class="auth-title">Double authentification</h1>
      <p class="auth-sub">Saisissez le code à 6 chiffres généré par votre application d'authentification.</p>

      <form @submit.prevent="submit">
        <div class="form-group">
          <input
            v-model="form.one_time_password"
            type="text"
            inputmode="numeric"
            maxlength="6"
            placeholder="000000"
            class="otp-input"
            :class="{ 'input-error': errors.one_time_password }"
            autofocus
            autocomplete="off"
          />
          <span v-if="errors.one_time_password" class="error-msg">{{ errors.one_time_password }}</span>
        </div>

        <button type="submit" class="btn-submit" :disabled="form.one_time_password.length !== 6">
          Vérifier
        </button>
      </form>

      <form method="POST" :action="route('logout')" class="logout-form">
        <input type="hidden" name="_token" :value="csrfToken" />
        <button type="submit" class="btn-logout">Se déconnecter</button>
      </form>
    </div>
  </div>
</template>

<script>
import { router, usePage } from '@inertiajs/vue3'

export default {
  name: 'TwoFactor',

  props: {
    errors: { type: Object, default: () => ({}) },
  },

  data() {
    return {
      form: { one_time_password: '' },
      csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    }
  },

  methods: {
    submit() {
      router.post(route('2fa.verify.submit'), this.form, {
        onError: (errors) => { this.errors = errors },
      })
    },
  },
}
</script>

<style scoped>
.auth-wrapper {
  min-height: 100vh;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.auth-card {
  background: white;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.08);
  padding: 48px 40px;
  width: 100%;
  max-width: 420px;
  text-align: center;
}
.auth-icon { font-size: 48px; margin-bottom: 16px; }
.auth-title { font-size: 22px; font-weight: 700; color: #1a1f2e; margin: 0 0 8px; }
.auth-sub { font-size: 14px; color: #6b7280; margin: 0 0 32px; line-height: 1.5; }
.form-group { margin-bottom: 20px; }
.otp-input {
  width: 100%;
  text-align: center;
  font-size: 32px;
  font-weight: 700;
  letter-spacing: 12px;
  padding: 16px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  color: #1a1f2e;
  font-family: monospace;
  box-sizing: border-box;
  transition: border-color 0.15s;
}
.otp-input:focus { outline: none; border-color: #4f6fee; }
.input-error { border-color: #ef4444 !important; }
.error-msg { display: block; font-size: 13px; color: #ef4444; margin-top: 8px; }
.btn-submit {
  width: 100%;
  padding: 14px;
  background: #4f6fee;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
  font-family: inherit;
}
.btn-submit:hover:not(:disabled) { background: #3d5cdb; }
.btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }
.logout-form { margin-top: 16px; }
.btn-logout {
  background: none;
  border: none;
  color: #9ca3af;
  font-size: 13px;
  cursor: pointer;
  text-decoration: underline;
  font-family: inherit;
}
</style>
