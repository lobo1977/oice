<template>
  <div>
    <group gutter="0" label-width="4em" label-margin-right="1em" label-align="left">
      <datetime class="datetime-picker" title="时间" v-model="info.create_time" format="YYYY-MM-DD HH:mm" :required="true"
        :min-hour=8 :max-hour=22 :minute-list="['00', '10', '15', '20', '30', '40', '45', '50']"></datetime>
      <x-input ref="input_title" title="摘要" v-model="info.title" :required="true" :max="10"
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
import { Group, Datetime, dateFormat, XInput, XTextarea, XButton } from 'vux'

export default {
  components: {
    Group,
    Datetime,
    dateFormat,
    XInput,
    XTextarea,
    XButton
  },
  data () {
    return {
      formValidate: false,
      id: 0,
      info: {
        __token__: '',
        owner_id: 0,
        title: '',        // 摘要
        summary: '',      // 详情
        create_time: dateFormat(new Date(), 'YYYY-MM-DD HH:mm') // 时间
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

      if (to.params.cid) {
        vm.info.owner_id = parseInt(to.params.cid)
        if (isNaN(vm.info.owner_id)) {
          vm.info.owner_id = 0
        }
      }

      vm.$get('/api/customer/log?id=' + vm.id + '&cid=' + vm.info.owner_id, (res) => {
        if (res.success) {
          if (vm.id) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            vm.$emit('on-view-loaded', '修改跟进纪要')
          } else {
            vm.info.__token__ = res.data.__token__
            vm.$emit('on-view-loaded', '添加跟进纪要')
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
    titleError () {
      this.$vux.toast.show({
        text: '请输入摘要'
      })
    },
    validateForm () {
      this.formValidate = this.$refs.input_title.valid
    },
    save () {
      this.validateForm()
      if (!this.formValidate) {
        return
      }

      this.$vux.loading.show({text: '请稍后...'})

      this.$post('/api/customer/log?id=' + this.id, this.info, (res) => {
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
.datetime-picker .weui-cell__ft {
  text-align:left;
}
</style>