<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-icon">🛡️</div>
      <h1 class="auth-title">Activation de la 2FA</h1>
      <p class="auth-sub">
        Scannez ce QR code avec <strong>Google Authenticator</strong> ou <strong>Authy</strong>,
        puis saisissez le code généré pour confirmer l'activation.
      </p>

      <div class="qr-wrapper">
        <img :src="qrCodeSvgUrl" alt="QR Code 2FA" class="qr-code" />
      </div>

      <div class="secret-block">
        <p class="secret-label">Clé manuelle</p>
        <code class="secret-code">{{ formattedSecret }}</code>
      </div>

      <form @submit.prevent="submit">
        <div class="form-group">
          <label>Code de vérification</label>
          <input
            v-model="form.one_time_password"
            type="text"
            inputmode="numeric"
            maxlength="6"
            placeholder="000000"
            class="otp-input"
            :class="{ 'input-error': errors.one_time_password }"
            autocomplete="off"
          />
          <span v-if="errors.one_time_password" class="error-msg">{{ errors.one_time_password }}</span>
        </div>

        <button type="submit" class="btn-submit" :disabled="form.one_time_password.length !== 6">
          Confirmer et activer
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'

export default {
  name: 'TwoFactorSetup',

  props: {
    qrCodeUrl: { type: String, required: true },
    secret:    { type: String, required: true },
    errors:    { type: Object, default: () => ({}) },
  },

  data() {
    return {
      form: { one_time_password: '' },
    }
  },

  computed: {
    qrCodeSvgUrl() {
      return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(this.qrCodeUrl)}`
    },
    formattedSecret() {
      return this.secret.match(/.{1,4}/g)?.join(' ') ?? this.secret
    },
  },

  methods: {
    submit() {
      router.post(route('2fa.setup.confirm'), this.form)
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
  max-width: 460px;
  text-align: center;
}
.auth-icon { font-size: 48px; margin-bottom: 16px; }
.auth-title { font-size: 22px; font-weight: 700; color: #1a1f2e; margin: 0 0 8px; }
.auth-sub { font-size: 14px; color: #6b7280; margin: 0 0 28px; line-height: 1.6; }
.qr-wrapper {
  background: #f8fafc;
  border-radius: 16px;
  padding: 20px;
  display: inline-block;
  margin-bottom: 20px;
}
.qr-code { width: 180px; height: 180px; display: block; }
.secret-block {
  background: #f1f5f9;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 24px;
}
.secret-label { font-size: 11px; color: #9ca3af; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px; }
.secret-code { font-family: monospace; font-size: 15px; color: #374151; letter-spacing: 2px; }
.form-group { margin-bottom: 20px; text-align: left; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
.otp-input {
  width: 100%;
  text-align: center;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: 10px;
  padding: 14px;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  color: #1a1f2e;
  font-family: monospace;
  box-sizing: border-box;
  transition: border-color 0.15s;
}
.otp-input:focus { outline: none; border-color: #4f6fee; }
.input-error { border-color: #ef4444 !important; }
.error-msg { display: block; font-size: 13px; color: #ef4444; margin-top: 6px; }
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
</style>
