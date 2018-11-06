<template>
  <div>
    <group gutter="0" label-width="4em" label-margin-right="1em" label-align="left">
      <cell :value="info.start_time" value-align="left" :is-link="true" @click.native="selectTime('start_time')">
        <span slot="title" :class="{warn: info.start_time.length == 0}">开始时间</span>
      </cell>
      <cell :value="info.end_time" value-align="left" :is-link="true" @click.native="selectTime('end_time')">
        <span slot="title">结束时间</span>
      </cell>
      <x-input ref="inpLogTitle" title="摘要" v-model="info.title" :required="true" :max="10"
        @on-click-error-icon="titleError" :should-toast-error="false" @on-change="validateForm"></x-input>
      <x-textarea placeholder="详情" :rows="3" v-model="info.summary" :max="500"></x-textarea>
    </group>
    <div class="bottom-bar">
      <x-button type="primary" class="bottom-btn" @click.native="save" :disabled="!formValidate">
        <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
      </x-button>
    </div>
  </div>
</template>

<script>
export default {
  components: {
  },
  data () {
    return {
      formValidate: false,
      id: 0,
      info: {
        __token__: '',
        level: 0,
        title: '',        // 摘要
        summary: '',      // 详情
        start_time: this.$dateFormat(new Date(), 'YYYY-MM-DD HH:mm'), // 时间
        end_time: ''      // 时间
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (to.params.id) {
        vm.id = parseInt(to.params.id)
        if (isNaN(vm.id)) {
          vm.id = 0
        }
      }

      vm.$get('/api/daily/edit?id=' + vm.id, (res) => {
        if (res.success) {
          if (vm.id) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            vm.$emit('on-view-loaded', '修改工作日报')
          } else {
            vm.info.__token__ = res.data.__token__
            vm.$emit('on-view-loaded', '添加工作日报')
          }
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    })
  },
  methods: {
    selectTime (field) {
      let vm = this
      vm.$vux.datetime.show({
        value: vm.info[field],
        cancelText: '取消',
        confirmText: '确定',
        format: 'YYYY-MM-DD HH:mm',
        minHour: 8,
        maxHour: 22,
        minuteList: ['00', '10', '15', '20', '30', '40', '45', '50'],
        onHide () {
          vm.validateForm()
        },
        onConfirm (val) {
          vm.info[field] = val
          vm.validateForm()
        }
      })
    },
    titleError () {
      this.$vux.toast.show({
        text: '请输入摘要'
      })
    },
    validateForm () {
      this.formValidate = this.info.start_time.length > 0 &&
        // this.info.end_time.length &&
        this.$refs.inpLogTitle.valid
    },
    save () {
      this.validateForm()
      if (!this.formValidate) {
        return
      }
      this.$vux.loading.show()
      this.$post('/api/daily/edit?id=' + this.id, this.info, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          if (this.id === 0) {
            this.id = res.data
          }
          this.$router.back()
        } else {
          this.info.__token__ = res.data
          this.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    }
  }
}
</script>

<style lang="less">
.warn {
  color: red;
}
</style>