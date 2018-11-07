<template>
  <div>
    <group gutter="0" label-width="4em" label-margin-right="1em" label-align="left">
      <cell title="工作评定" @click.native="showLevelPicker = true" :is-link="true" :value="levelText" value-align="left"></cell>
      <x-textarea placeholder="详情" :rows="3" v-model="info.content" :max="500"></x-textarea>
    </group>

    <actionsheet v-model="showLevelPicker" :menus="levelPickerList" theme="android" @on-click-menu="selectLevel"></actionsheet>

    <div class="bottom-bar">
      <x-button type="primary" class="bottom-btn" @click.native="save" :disabled="!formValidate">
        <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
      </x-button>
    </div>
  </div>
</template>

<script>
import levelData from '../../data/review_level.json'

export default {
  components: {
  },
  data () {
    return {
      formValidate: true,
      id: 0,
      info: {
        __token__: '',
        level: 1,
        review_user: 0,
        review_date: '',
        content: ''
      },
      levelText: '',
      showLevelPicker: false,
      levelPickerList: levelData
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (to.params.id) {
        vm.id = parseInt(to.params.id)
        if (isNaN(vm.id)) {
          vm.id = 0
        }
      } else if (to.params.user && to.params.date) {
        vm.review_user = to.params.user
        vm.review_date = to.params.date
      }

      vm.$get('/api/daily/editReview?id=' + vm.id, (res) => {
        if (res.success) {
          if (vm.id) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            vm.$emit('on-view-loaded', '修改批阅')
          } else {
            vm.info.__token__ = res.data.__token__
            vm.$emit('on-view-loaded', '批阅工作日报')
          }
          vm.levelText = vm.levelPickerList[vm.info.level].label
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '20em'
          })
        }
      })
    })
  },
  methods: {
    selectLevel (key, item) {
      this.info.level = item.value
      this.levelText = item.label
    },
    validateForm () {
      this.formValidate = true
    },
    save () {
      this.validateForm()
      if (!this.formValidate) {
        return
      }
      this.$vux.loading.show()
      this.$post('/api/daily/editReview?id=' + this.id, this.info, (res) => {
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
</style>