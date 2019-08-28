<template>
  <div style="height:100%;padding-top:46px;overflow:auto;">
    <x-header class="fix-top"
      :left-options="leftOptions" :title="pageTitle">
      <span slot="overwrite-left">
        <x-icon type="close" size="20" class="icon-close" @click="close"></x-icon>
      </span>
    </x-header>

    <search v-show="step === 1"
      @on-change="filter"
      position="absolute"
      auto-scroll-to-top top="46px"
      @on-focus="onFocus"
      @on-cancel="onCancel"
      @on-submit="onSubmit"
      ref="searchContact">
      <x-button slot="right" mini type="primary" @click.native="selectAll" style="margin-left:10px;">全选</x-button>
    </search>

    <div v-show="step == 1 && !isSearching">
      <sticky :offset="92">
        <tab>
          <tab-item :selected="type === 0" @on-item-click="filterType(0)">群</tab-item>
          <tab-item :selected="type === 1" @on-item-click="filterType(1)">联系人</tab-item>
        </tab>
      </sticky>
    </div>

    <group :gutter="0" v-show="step === 1" style="padding-bottom:90px;">
      <cell v-for="(item, index) in filterContact" :key="index" :title="item.contact_name">
        <!-- <img slot="icon" :src="'https://wx.qq.com' + item.contact_avatar" class="cell-image" /> -->
        <div solt="default">
          <check-icon :value.sync="item.checked" @update:value="check"></check-icon>
        </div>
      </cell>
    </group>
    
    <div class="fix-bottom" v-show="step === 1">
      <span style="font-size:0.9em;">{{'已选择：' + checkCount + '个'}}</span>
      <x-button mini type="primary" @click.native="editMessage" 
        :disabled="checkCount <= 0" style="float:right;margin-right:30px;">下一步</x-button>
    </div>

    <group v-if="step === 2" :title="'将发送消息给' + checkCount + '个联系人/群'" 
      label-width="80px" label-margin-right="1em">
      <x-textarea :max="500" v-model="message" :height="180"></x-textarea>
      <x-switch title="循环发送" inline-desc="" v-model="cycle"></x-switch>
      <cell title="发送间隔" value-align="left">
        <inline-x-number style="float:left;margin:0 5px 0 0;" width="50px" 
          :min="1" :max="8" :step="1" v-model="cycle_hour"></inline-x-number>
        <div style="float:left;display:inline-block;line-height:28px;">小时</div>
      </cell>
      <cell title="发送时段" :inline-desc="'每日' + start_hour + '点开始'" primary="content">
        <range v-model="start_hour" :max="24"></range>
      </cell>
      <cell title="发送时段" :inline-desc="'每日' + end_hour + '点停止'" primary="content">
        <range v-model="end_hour" :min="start_hour" :max="24"></range>
      </cell>
    </group>

    <div class="bottom-bar" v-if="step === 2">
      <x-button type="primary" class="bottom-btn"
        @click.native="pushMessage" :disabled="message.length === 0">发送</x-button>
    </div>
  </div>
</template>

<script>
import { XHeader, Search, CheckIcon, Range } from 'vux'

export default {
  name: 'robotpush',
  props: {
    robot: null,
    content: '',
    url: '',
    isShown: {
      type: Boolean,
      default: false
    }
  },
  components: {
    XHeader,
    Search,
    CheckIcon,
    Range
  },
  data () {
    return {
      step: 1,
      robotID: 0,
      message: '',
      isSearching: false,
      type: 0,
      keyword: '',
      contact: [],
      all: false,
      link: this.url,
      checkCount: 0,
      checkList: [],
      cycle: true,
      cycle_hour: 2,
      start_hour: 8,
      end_hour: 20,
      leftOptions: {
        showBack: false
      }
    }
  },
  computed: {
    filterContact () {
      return this.contact.filter(item => {
        if (this.keyword.length) {
          return item.contact_name.indexOf(this.keyword) >= 0
        } else {
          return item.type === this.type
        }
      })
    },
    pageTitle () {
      if (this.step === 1) {
        return '选择收信人'
      } else {
        return '编辑消息'
      }
    }
  },
  methods: {
    editMessage () {
      this.checkList = []
      if (this.checkCount > 0) {
        if (!this.all || this.keyword.length > 0) {
          this.contact.forEach((item, index) => {
            if (item.checked) {
              this.checkList.push(item.id)
            }
          })
        }
        this.step = 2
      }
    },
    close () {
      this.$emit('on-close')
    },
    filter (val) {
      this.contact.forEach((item, index) => {
        item.checked = false
      })
      this.checkCount = 0
      this.all = false
      this.keyword = val
    },
    filterType (val) {
      this.contact.forEach((item, index) => {
        item.checked = false
      })
      this.checkCount = 0
      this.all = false
      this.type = val
    },
    onFocus () {
      this.isSearching = true
    },
    onSubmit (val) {
      this.$refs.searchCustomer.setBlur()
      this.keyword = val
    },
    onCancel () {
      this.isSearching = false
      this.keyword = ''
    },
    check (checked) {
      this.all = false
      checked ? this.checkCount++ : this.checkCount--
      return false
    },
    selectAll () {
      this.all = !this.all
      this.checkCount = 0
      if (this.all) {
        this.contact.filter(item => {
          if (this.keyword.length) {
            return item.contact_name.indexOf(this.keyword) >= 0
          } else {
            return item.type === this.type
          }
        }).forEach((item, index) => {
          item.checked = true
          this.checkCount++
        })
      } else {
        this.contact.forEach((item, index) => {
          item.checked = false
        })
      }
    },
    pushMessage () {
      let vm = this
      if (vm.checkCount > 0) {
        vm.$vux.loading.show()
        vm.$post('/api/robot/push', {
          type: vm.all && vm.keyword.length === 0 ? vm.type : -1,
          contact: vm.all && vm.keyword.length === 0 ? '' : vm.checkList.join(','),
          content: vm.message,
          url: vm.link,
          cycle: vm.cycle ? vm.cycle_hour : 0,
          start: vm.start_hour,
          end: vm.end_hour
        }, (res) => {
          vm.$vux.loading.hide()
          if (res.success) {
            vm.$vux.toast.show({
              type: 'success',
              text: '群发消息已加入任务列表。',
              width: '15em',
              onHide () {
                vm.close()
              }
            })
          } else {
            vm.$vux.toast.show({
              text: res.message,
              width: '15em'
            })
          }
        })
      }
    }
  },
  watch: {
    isShown (val) {
      if (!val) return
      let vm = this
      vm.step = 1
      vm.checkCount = 0
      vm.keyword = ''
      vm.checkList = []
      vm.cycle = true
      vm.cycle_hour = 2
      vm.start_hour = 8
      vm.end_hour = 20
      vm.$get('/api/robot/contact?id=' + this.robotID, (res) => {
        if (res.success) {
          if (res.data && res.data.length) {
            this.contact = res.data
          } else {
            this.$vux.alert.show({
              title: '机器人未登录',
              content: '请先登录微信机器人',
              onHide () {
                vm.$router.push({name: 'Robot'})
              }
            })
          }
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    robot (val) {
      if (val) {
        this.robotID = val.id
      } else {
        this.robotID = 0
      }
    },
    content (val) {
      this.message = val
    }
  }
}
</script>

<style>
.icon-close {
  position:relative;
  fill:#ccc;
}

.fix-top {
  position:fixed;
  top:0;
  z-index:100;
  width:100%;
}

.fix-bottom {
  position:fixed;
  bottom:0;
  width:100%;
  background-color:#fff;
  padding:10px 15px;
}

.enable {
  color:#fff;
}
</style>
