/**
 * @file
 * Drupal behavior for KP Zadarma block with Vue.js integration.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Timeout (ms) before auto-closing the success modal.
   */
  const AUTO_CLOSE_TIMEOUT = 20000;

  Drupal.behaviors.kpZadarmaBlock = {
    attach(context) {
      Object.entries(drupalSettings.kp_zadarma || {}).forEach(([id, settings]) => {
        const element = context.querySelector(`#${id}`);
        if (element && !element.dataset.vueMounted) {
          this.vueAppAttach(settings, id);
          element.dataset.vueMounted = true;
        }
      });
    },

    vueAppAttach: function(item, attr_id) {
      Vue.directive('click-outside', {
        bind: function(el, binding, vnode) {
          el.clickOutsideEvent = function (event) {
            if (!(el === event.target || el.contains(event.target))) {
              vnode.context[binding.expression](event);
            }
          };
          document.body.addEventListener('click', el.clickOutsideEvent);
        },
        unbind: function(el) {
          document.body.removeEventListener('click', el.clickOutsideEvent);
        }
      });

      new Vue({
        el: '#' + attr_id,
        template: `<div class="wrapper-zadarma-api">
          <div v-if="!isModal && isActive" @click="showModal" class="zadarma-link"></div>
          <div v-if="isModal" class="modal-bg"></div>
          <div v-if="isModal" class="modal-content" v-click-outside="closeModal">
            <div class="modal-title">
              <span>{{ Drupal.t('Do you have questions? We will help you!') }}</span>
              <span class="close" @click="closeModal">X</span>
            </div>
            <div :class="classBlock">{{ successText }}</div>
            <div v-if="!isSuccess" class="form">
              <vue-tel-input @input="validatePhone" v-bind="propsElement"></vue-tel-input>
              <button @click="submitPhone" type="submit" :disabled="isDisabled">{{ Drupal.t("I'm waiting for call!") }}</button>
            </div>
            <div v-if="isError && !isSuccess" class="error-message">{{ Drupal.t('Check that the phone number you are calling is correct.') }}</div>
            <div v-if="!isSuccess" class="zadarma-modal-privacy">{{ Drupal.t('By using callback, you agree that your data will be transmitted to AWHelp and that you have read the privacy policy.') }}</div>
          </div>
        </div>`,
        data: {
          item: item,
          isModal: false,
          isError: false,
          isActive: true,
          isSuccess: false,
          phoneValue: '',
          isDisabled: true,
          successText: Drupal.t('Our best manager will call you back in 60 seconds. It will be quick and for free!'),
          propsElement: {
            mode: 'international',
            inputOptions: {
              type: 'tel',
              maxlength: 18,
              placeholder: Drupal.t('Enter a phone number'),
            }
          }
        },
        computed: {
          classBlock() {
            return this.isSuccess ? 'success-message' : 'zadarma-modal-description';
          }
        },
        methods: {
          validatePhone(phone, phoneObject) {
            this.phoneValue = phone;
            this.isError = false;
            this.isDisabled = true;
            if (phoneObject.valid === true) {
              this.isDisabled = false;
            } else if (phone && phoneObject.valid === false) {
              this.isError = true;
            }
          },
          showModal() {
            this.isModal = true;
          },
          submitPhone() {
            this.isDisabled = true;

            axios.get('/session/token').then(res => {
              const token = res.data;
              axios.post(
                this.item.url,
                {
                  [this.item.phone_key]: this.phoneValue
                },
                {
                  headers: {
                    'X-CSRF-Token': token,
                    'Content-Type': 'application/json'
                  }
                }
              )
                .then((resp) => {
                  if (resp.data.data) {
                    let dataVal = resp.data.data;
                    const data = (typeof dataVal === 'string') ? JSON.parse(dataVal) : dataVal;
                    if (data.status === 'success') {
                      this.isSuccess = true;
                      setTimeout(() => { this.closeModal(); }, AUTO_CLOSE_TIMEOUT);
                    }
                  }
                })
                .catch((error) => {
                  if (error.response) {
                    if (error.response.status === 400) {
                      this.isError = true;
                    }
                    else {
                      console.error('Submission error:', error.response.status, error.response.data);
                    }
                  }
                  else {
                    console.error('Submission error:', error.message);
                  }
                });
            });
          },
          closeModal() {
            this.isModal = false;
          },
          shouldSkipByDateName(dataTime, dayName) {
            switch (dataTime) {
              case 'weekday':
                if (dayName === 'Sunday' || dayName === 'Saturday') {
                  return true;
                }
                break;
              case 'day_off':
                if (dayName !== 'Sunday' && dayName !== 'Saturday') {
                  return true;
                }
                break;
            }
            return false;
          }
        },
        mounted() {
          if (this.item.settings && Object.keys(this.item.settings).length > 0) {
            const dt = new Date();
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const hour = dt.getHours();
            const minute = dt.getMinutes();
            const dayName = days[dt.getDay()];
            this.isActive = false;
            const settingsKeys = Object.keys(this.item.settings);
            for (let i = 0; i < settingsKeys.length; i++) {
              const key = settingsKeys[i];
              if (this.shouldSkipByDateName(key, dayName)) {
                continue;
              }
              const timeTo = this.item.settings[key].to.split(':').map(Number);
              const timeFrom = this.item.settings[key].from.split(':').map(Number);
              if ((hour > timeFrom[0] && hour < timeTo[0]) ||
                (hour === timeFrom[0] && minute >= timeFrom[1]) ||
                (hour === timeTo[0] && minute <= timeTo[1])) {
                this.isActive = true;
                break;
              }
            }
          }
        }
      });
    }
  };
})(Drupal, drupalSettings);
