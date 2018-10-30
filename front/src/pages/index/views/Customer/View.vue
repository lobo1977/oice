<template>
  <div>
    <topalert v-if="info.id > 0 && info.clash > 0" message="该客户已撞单，暂时不能跟进，请等候管理员处理。"></topalert>

    <tab>
      <tab-item @on-item-click="goTab(0)" :selected="tab === 0">基本信息</tab-item>
      <tab-item @on-item-click="goTab(1)" :selected="tab === 1">跟进纪要</tab-item>
      <tab-item @on-item-click="goTab(2)" :selected="tab === 2">项目筛选</tab-item>
      <tab-item @on-item-click="goTab(3)" :selected="tab === 3">推荐资料</tab-item>
    </tab>

    <div v-show="tab === 0 && !showSelectUser">
      <flow v-if="info.id > 0 && info.status < 6">
        <flow-state title="潜在" is-done></flow-state>
        <flow-line :is-done="info.status > 0" :tip="flowOneText"></flow-line>
        <flow-state title="跟进" :is-done="info.status > 0"></flow-state>
        <flow-line :is-done="info.status > 1" :tip="flowTwoText"></flow-line>
        <flow-state title="看房" :is-done="info.status > 1"></flow-state>
        <flow-line :is-done="info.status > 2" :tip="flowThreeText"></flow-line>
        <flow-state title="确认" :is-done="info.status > 2"></flow-state>
        <flow-line :is-done="info.status > 3" :tip="flowFourText"></flow-line>
        <flow-state v-if="info.status < 5" state="√" title="成交" :is-done="info.status === 4"></flow-state>
        <flow-state v-if="info.status === 5" state="×" title="失败" is-done class="state-fail"></flow-state>
      </flow>

      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="所在地" value-align="left" :value="info.area" v-show="info.area.length > 0"></cell>
        <cell title="详细地址" value-align="left" :value="info.address" v-show="info.address.length > 0"></cell>
        <cell title="需求项目" value-align="left" :value="info.demand" v-show="info.demand.length > 0"></cell>
        <cell title="租购" value-align="left" :value="info.lease_buy" v-show="info.lease_buy.length > 0"></cell>
        <cell title="意向商圈" value-align="left" :value="info.district" v-show="info.district.length > 0"></cell>
        <cell title="面积" value-align="left" :value="info.acreage" v-show="info.acreage.length > 0"></cell>
        <cell title="预算" value-align="left" :value="info.budget" v-show="info.budget.length > 0"></cell>
        <cell title="入驻日期" value-align="left" :value="info.settle_date|formatDate" v-show="info.settle_date.length > 0"></cell>
        <cell title="在驻面积" value-align="left" :value="info.current_area + ' 平米'" v-show="info.current_area > 0"></cell>
        <cell title="到期日" value-align="left" :value="info.end_date|formatDate" v-show="info.end_date.length > 0"></cell>
      </group>

      <group title="备注" v-if="info.rem.length > 0">
        <p class="group-padding">{{ info.rem }}</p>
      </group>

      <group v-if="info.linkman.length || info.allowEdit">
        <group-title slot="title">联系人
          <router-link style="float:right;color:#333;cursor:pointer;" v-if="info.allowEdit"
            :to="{name: 'LinkmanEdit', params: {id: 0, type: 'customer', oid: info.id}}">+ 添加</router-link>
        </group-title>
        <cell v-for="(item, index) in info.linkman" :key="index"
          :title="item.title" :link="{name: 'Linkman', params: {id: item.id}}" 
          :inline-desc="item.desc"></cell>
      </group>

      <group v-if="info.manager || info.allowTurn">
        <group-title slot="title">客户经理
          <a style="float:right;color:#333;cursor:pointer;" v-if="info.allowTurn"
            @click="showSelectUser = true">转交</a>
        </group-title>
        <cell :title="info.manager" :inline-desc="info.company || info.manager_mobile"
          :link="{name: 'UserView', params: {id: info.user_id}}">
          <img slot="icon" :src="info.avatar" class="cell-image">
        </cell>
      </group>

      <group v-if="info.clashCustomer" title="被撞单客户">
        <cell :title="info.clashCustomer.name"
          :link="{name: 'CustomerView', params: {id: info.clashCustomer.id}}"
          :inline-desc="info.clashCustomer.manager + ' ' + info.clashCustomer.update_time">
        </cell>
      </group>

      <group v-if="info.confirm.length">
        <group-title slot="title">
          确认书
        </group-title>
        <cell v-for="(item, index) in info.confirm" :key="index"
          :title="item.title" :link="{name: 'ConfirmView', params: {id: item.id}}" 
          :inline-desc="item.desc"></cell>
      </group>

      <actionsheet v-if="info.clash > 0 && info.allowClash" v-model="showClashMenu" 
        :menus="menuClash" @on-click-menu="clashMenuClick"></actionsheet>

      <flexbox :gutter="0" class="bottom-bar">
        <flexbox-item :span="6">
          <x-button type="primary" class="bottom-btn"
            v-if="!info.clash || !info.allowClash"
            :disabled="!info.allowFollow"
            :link="{name: 'CustomerLog', params: {id:0, cid: info.id}}">
            <x-icon type="refresh" class="btn-icon"></x-icon> 跟进
          </x-button>
          <x-button type="primary" class="bottom-btn" 
            v-if="info.clash > 0 && info.allowClash"
            @click.native="showClashMenu = true">
            <x-icon type="flash-off" class="btn-icon"></x-icon> 撞单处理
          </x-button>
        </flexbox-item>
        <flexbox-item :span="4">
          <x-button type="warn" class="bottom-btn" :disabled="!info.allowEdit"
            :link="{name:'CustomerEdit', params: { id: info.id }}">
            <x-icon type="compose" class="btn-icon"></x-icon> 修改资料
          </x-button>
        </flexbox-item>
        <flexbox-item :span="2">
          <x-button type="default" class="bottom-btn" :disabled="!info.allowDelete"
            @click.native="remove">
            <x-icon type="trash-a" class="btn-icon"></x-icon>
          </x-button>
        </flexbox-item>
      </flexbox>
    </div>

    <popup v-model="showSelectUser" position="bottom"
      height="100%" style="overflow-y:hidden;">
      <user-selecter :is-shown="showSelectUser" :company="info.company_id"
        @on-close="showSelectUser = false" @on-confirm="setManager"></user-selecter>
    </popup>

    <div v-show="tab === 1" class="time-line">
      <timeline>
        <timeline-item v-for="(item, index) in info.log" :key="index">
          <h4>
            <span>{{item.title}}</span>
            <span @click="editLog(item.id)">
              <x-icon v-if="item.allowEdit" 
                type="compose" size="20"></x-icon>
            </span>
            <span @click="removeLog(item.id)">
              <x-icon v-if="item.allowDelete" 
                type="close" size="18"></x-icon>
            </span>
          </h4>
          <p v-if="item.summary" v-html="item.summary"></p>
          <p class="foot">{{item.user}} &nbsp; {{item.create_time|formatTime}}</p>
        </timeline-item>
      </timeline>

      <div class="bottom-bar">
        <x-button type="warn" class="bottom-btn" :disabled="!info.allowFollow"
          :link="{name: 'CustomerLog', params: {id:0, cid: info.id}}">
          <x-icon type="plus" class="btn-icon"></x-icon> 添加
        </x-button>
      </div>
    </div>

    <div v-show="tab === 2">
      <group :gutter="0">
        <swipeout>
          <swipeout-item v-for="(item, index) in info.filter" :key="index" transition-mode="follow"
            @on-open="swipeoutOpen(item)" @on-close="swipeoutClose(item)"
            @mousedown.native="itemMouseDown" @mouseup.native="itemMouseUp" 
            @touchstart.native="itemMouseDown" @touchend.native="itemMouseUp"
            @click.native="itemClick(item)">
            <div slot="right-menu" v-if="info.allowFollow">
              <swipeout-button v-if="index > 0" @click.native.stop="sortFilter(item, true)" type="primary">上移</swipeout-button>
              <swipeout-button v-if="index < info.filter.length - 1" @click.native.stop="sortFilter(item, false)" type="default">下移</swipeout-button>
              <swipeout-button @click.native.stop="removeFilter(item)" type="warn">删除</swipeout-button>
            </div>
            <cell slot="content" :title="item.title">
              <p slot="inline-desc" class="cell-desc">{{item.desc}}</p>
              <div solt="default" v-if="info.allowFollow">
                <check-icon :value.sync="item.checked" @update:value="check"></check-icon>
              </div>
            </cell>
          </swipeout-item>
        </swipeout>
      </group>

      <actionsheet v-model="showPrintPicker" :menus="printMode" theme="android" @on-click-menu="choseMode"></actionsheet>

      <flexbox :gutter="0" class="bottom-bar">
        <flexbox-item :span="4">
          <x-button type="warn" class="bottom-btn" @click.native="toRecommend" 
            :disabled="!info.allowFollow || checkCount <= 0">
            <x-icon type="share" class="btn-icon"></x-icon> 生成推荐资料
          </x-button>
        </flexbox-item>
        <flexbox-item :span="4">
          <x-button type="primary" class="bottom-btn" @click.native="toConfirm" 
            :disabled="!info.allowConfirm || checkCount != 1">
            <x-icon type="checkmark-circled" class="btn-icon"></x-icon> 云确认
          </x-button>
        </flexbox-item>
        <flexbox-item>
          <x-button type="default" class="bottom-btn" :disabled="!info.allowFollow"
            :link="{name:'Favorite', query: { cid: info.id }}">
            <x-icon type="plus" class="btn-icon"></x-icon> 添加筛选
          </x-button>
        </flexbox-item>
      </flexbox>
    </div>

    <div v-show="tab === 3">
      <group :gutter="0">
        <swipeout>
          <swipeout-item v-for="(item, index) in info.recommend" :key="index"
            @on-open="swipeoutOpen(item)" @on-close="swipeoutClose(item)"
            @mousedown.native="itemMouseDown" @mouseup.native="itemMouseUp" 
            @touchstart.native="itemMouseDown" @touchend.native="itemMouseUp"
            transition-mode="follow">
            <div slot="right-menu" v-if="info.allowFollow">
              <swipeout-button @click.native="removeRecommend(item.id)" type="warn">删除</swipeout-button>
            </div>
            <cell slot="content" :disabled="item.disabled" is-link @click.native.stop="print(item)">
              <p slot="title">{{item.building}}
                <span v-if="item.building_count > 1">等 {{item.building_count}} 个项目</span>
              </p>
              <p slot="inline-desc" class="cell-desc">{{item.mode|printModeLabel}} {{item.create_time}}</p>
            </cell>
          </swipeout-item>
        </swipeout>
      </group>
    </div>
  </div>
</template>

<script>
import { Flow, FlowState, FlowLine, GroupTitle,
  Swipeout, SwipeoutItem, SwipeoutButton, CheckIcon,
  Timeline, TimelineItem } from 'vux'
import Topalert from '@/components/Topalert.vue'
import UserSelecter from '../User/Selecter.vue'
import printModeData from '../../data/print_mode.json'

export default {
  components: {
    Flow,
    FlowState,
    FlowLine,
    GroupTitle,
    Swipeout,
    SwipeoutItem,
    SwipeoutButton,
    CheckIcon,
    Timeline,
    TimelineItem,
    Topalert,
    UserSelecter
  },
  data () {
    return {
      tab: 0,
      showWarn: true,
      info: {
        id: 0,
        customer_name: '',    // 名称
        area: '',             // 城区
        address: '',          // 地址
        demand: '',           // 需求项目
        lease_buy: '',        // 租赁/购买
        district: '',         // 意向商圈
        acreage: '',          // 面积
        budget: '',           // 预算
        settle_date: '',      // 入驻日期
        current_area: '',     // 在驻面积
        end_date: '',         // 到日期
        rem: '',              // 备注
        status: 0,            // 状态
        clash: 0,             // 撞单
        user_id: 0,
        company_id: 0,
        manager: '',          // 客户经理
        avatar: '',
        manager_mobile: '',
        company: '',          // 所属企业
        allowEdit: false,
        allowTurn: false,
        allowFollow: false,
        allowConfirm: false,
        allowClash: false,
        allowDelete: false,
        linkman: [],            // 联系人
        log: [],                // 跟进纪要
        filter: [],             // 项目筛选表
        recommend: [],          // 推荐资料
        confirm: [],            // 确认书
        clashCustomer: null
      },
      showSelectUser: false,
      checkCount: 0,
      showPrintPicker: false,
      printMode: printModeData,
      showClashMenu: false,
      menuClash: [
        {
          label: '强行转交',
          type: 'primary',
          value: 0
        },
        {
          label: '并行处理',
          type: 'default',
          value: 1
        },
        {
          label: '驳回',
          type: 'warn',
          value: 2
        }
      ],
      pageX: null,
      pageY: null,
      mouseMove: false
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (to.query.tab || to.query.tab === 0) {
        vm.tab = parseInt(to.query.tab)
        if (isNaN(vm.tab)) {
          vm.tab = 0
        }
      }
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.getInfo(id)
      }
    })
  },
  beforeRouteUpdate (to, from, next) {
    let vm = this
    if (to.query.tab || to.query.tab === 0) {
      vm.tab = parseInt(to.query.tab)
      if (isNaN(vm.tab)) {
        vm.tab = 0
      }
    }
    let id = parseInt(to.params.id)
    if (!isNaN(id)) {
      if (id !== vm.info.id) {
        vm.getInfo(id)
      }
    }
    next()
  },
  methods: {
    new () {
      this.$router.push({name: 'CustomerEdit', params: {id: 0}})
    },
    goTab (tab) {
      this.$router.replace({name: 'CustomerView', params: {id: this.info.id}, query: {tab: tab}})
    },
    getInfo (id) {
      let vm = this
      vm.$vux.loading.show()
      vm.$get('/api/Customer/detail?id=' + id, (res) => {
        vm.$vux.loading.hide()
        if (res.success) {
          for (let item in vm.info) {
            if (res.data[item] !== undefined && res.data[item] !== null) {
              vm.info[item] = res.data[item]
            }
          }

          vm.$emit('on-view-loaded', vm.info.customer_name)

          if (vm.$isWechat()) {
            let shareLink = window.location.href
            let shareDesc = vm.info.lease_buy + vm.info.demand + ' ' + vm.info.acreage
            let shareImage = window.location.protocol + '//' +
              window.location.host + '/static/img/logo.png'

            vm.$wechatShare(null, shareLink, vm.info.customer_name, shareDesc, shareImage)
          }
        } else {
          vm.info.id = 0
          vm.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    setManager (newUser) {
      let vm = this
      if (!newUser) return
      if (newUser.id === vm.info.user_id) return
      vm.$vux.confirm.show({
        title: '转交客户',
        content: '确定要将客户转交给 <strong>' + newUser.title + '</strong> 吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/customer/turn', {
            id: vm.info.id,
            user_id: newUser.id
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              vm.$vux.toast.show({
                type: 'success',
                text: '转交成功。',
                width: '12em',
                onHide () {
                  vm.$router.back()
                }
              })
            } else {
              vm.$vux.toast.show({
                text: res.message,
                width: '13em'
              })
            }
          })
        }
      })
    },
    remove () {
      let vm = this
      vm.$vux.confirm.show({
        title: '删除客户',
        content: '确定要删除客户 <strong>' + vm.info.customer_name + '</strong> 吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/customer/remove', {
            id: vm.info.id
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              vm.$router.back()
            } else {
              vm.$vux.toast.show({
                text: res.message,
                width: '13em'
              })
            }
          })
        }
      })
    },
    editLog (id) {
      this.$router.push({
        name: 'CustomerLog',
        params: {id: id, cid: this.info.id}
      })
    },
    removeLog (id) {
      let vm = this
      vm.$vux.confirm.show({
        title: '删除跟进纪要',
        content: '确定要删除这条跟进纪要吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/customer/removeLog', {
            id: id
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              for (let i in vm.info.log) {
                if (vm.info.log[i].id === id) {
                  vm.info.log.splice(i, 1)
                  break
                }
              }
            } else {
              vm.$vux.toast.show({
                text: res.message,
                width: '13em'
              })
            }
          })
        }
      })
    },
    check (checked) {
      checked ? this.checkCount++ : this.checkCount--
      return false
    },
    getSelectedList () {
      let selectedList = []
      this.info.filter.forEach((item, index) => {
        if (item.checked) {
          selectedList.push('' + item.building_id + ',' + item.unit_id)
        }
      })
      return selectedList
    },
    itemMouseDown (event) {
      this.pageX = event.pageX
      this.pageY = event.pageY
    },
    itemMouseUp (event) {
      if (this.pageX !== event.pageX || this.pageY !== event.pageY) {
        this.mouseMove = true
      } else {
        this.mouseMove = false
      }
    },
    itemClick (item) {
      if (this.mouseMove || item.disabled) return
      if (item.unit_id) {
        this.$router.push({name: 'Unit', params: {id: item.unit_id}})
      } else if (item.building_id) {
        this.$router.push({name: 'BuildingView', params: {id: item.building_id}})
      }
    },
    sortFilter (item, up) {
      this.$vux.loading.show()
      this.$post('/api/customer/sortFilter', {
        id: this.info.id,
        building_id: item.building_id,
        unit_id: item.unit_id,
        up: up ? 1 : 0
      }, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          this.info.filter = res.data
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '13em'
          })
        }
      })
    },
    removeFilter (item) {
      this.$vux.loading.show()
      this.$post('/api/customer/removeFilter', {
        id: this.info.id,
        building_id: item.building_id,
        unit_id: item.unit_id
      }, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          this.info.filter = res.data
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '13em'
          })
        }
      })
    },
    toRecommend () {
      let vm = this
      if (vm.checkCount < 1) {
        vm.$vux.toast.show({
          text: '请选择要推荐的项目。',
          width: '12em'
        })
      } else {
        this.showPrintPicker = true
      }
    },
    toConfirm () {
      let vm = this
      if (vm.checkCount < 1) {
        vm.$vux.toast.show({
          text: '请选择要生成确认书的项目。',
          width: '15em'
        })
      } else if (vm.checkCount > 1) {
        vm.$vux.toast.show({
          text: '只能选择一个项目生成确认书。',
          width: '16em'
        })
      } else {
        let bid = 0
        for (let i = 0; i < vm.info.filter.length; i++) {
          if (vm.info.filter[i].checked) {
            bid = vm.info.filter[i].building_id
            break
          }
        }
        if (bid === 0) return
        this.$router.push({
          name: 'ConfirmEdit',
          params: {id: 0, bid: bid, cid: vm.info.id}
        })
      }
    },
    choseMode (key, item) {
      let vm = this
      vm.$vux.loading.show()
      vm.$post('/api/customer/recommend', {
        cid: vm.info.id,
        mode: item.value,
        ids: vm.getSelectedList()
      }, (res) => {
        vm.$vux.loading.hide()
        if (res.success) {
          vm.info.recommend = res.data
          vm.goTab(3)
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    clashMenuClick (key, item) {
      let vm = this
      if (item.value === 2) {
        vm.$vux.confirm.show({
          title: '驳回',
          content: '当前客户信息将被删除，确定要驳回处理吗？',
          onConfirm () {
            vm.clashPass(item.value)
          }
        })
      } else if (item.value === 0) {
        vm.$vux.confirm.show({
          title: '强行转交',
          content: '被撞单客户将转交给当前用户，当前客户信息将被删除，确定要强行转交吗？',
          onConfirm () {
            vm.clashPass(item.value)
          }
        })
      } else {
        vm.clashPass(item.value)
      }
    },
    clashPass (operate) {
      let vm = this
      vm.$vux.loading.show()
      vm.$post('/api/customer/clashPass', {
        id: vm.info.id,
        operate: operate
      }, (res) => {
        vm.$vux.loading.hide()
        if (res.success) {
          this.$vux.toast.show({
            type: 'success',
            text: '撞单处理完成。',
            width: '15em',
            onHide () {
              vm.$router.push({name: 'Customer', query: {reload: 1}})
            }
          })
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    },
    swipeoutOpen (item) {
      item.disabled = true
    },
    swipeoutClose (item) {
      item.disabled = false
    },
    print (item) {
      if (this.mouseMove || item.disabled) return
      if (item.mode === 0) {
        this.$router.push({name: 'RecommendView', params: {id: item.token}})
      } else {
        document.location = '/index/print/' + item.token + '/' + item.mode
      }
    },
    removeRecommend (id) {
      let vm = this
      vm.$vux.confirm.show({
        title: '删除资料',
        content: '删除后，已分享给客户的资料将无法查看，确定要删除吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/customer/removeRecommend', {
            id: id
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              for (let i = vm.info.recommend.length - 1; i >= 0; i--) {
                if (vm.info.recommend[i].id === id) {
                  vm.info.recommend.splice(i, 1)
                }
              }
            } else {
              vm.$vux.toast.show({
                text: res.message,
                width: '13em'
              })
            }
          })
        }
      })
    }
  },
  filters: {
    printModeLabel (type) {
      if (!printModeData) {
        return ''
      } else {
        for (let i in printModeData) {
          if (printModeData[i].value === type) {
            return printModeData[i].label
          }
        }
      }
      return ''
    }
  },
  computed: {
    flowOneText () {
      if (this.info.status === 0) {
        return '进行中'
      } else {
        return null
      }
    },
    flowTwoText () {
      if (this.info.status === 1) {
        return '进行中'
      } else {
        return null
      }
    },
    flowThreeText () {
      if (this.info.status === 2) {
        return '进行中'
      } else {
        return null
      }
    },
    flowFourText () {
      if (this.info.status === 3) {
        return '进行中'
      } else {
        return null
      }
    }
  }
}
</script>

<style lang="less">
.state-fail .weui-wepay-flow__state {
  background-color: red !important;
}

.weui-panel__ft {
  position: absolute;
  top: 0;
  right: 0;
}

.time-line {
  .vux-timeline {
    padding:0.8em;
  }
  .vux-timeline-item-content {
    padding-bottom:0.5em;
    h4 {
      font-size:0.8em;
      color: #666;
      font-weight: bold;
      .vux-x-icon {
        position: relative;
        top:4px;
        cursor:pointer;
      }
      .vux-x-icon-compose {
        margin-left:3px;
        fill:rgb(4, 190, 2);
      }
      .vux-x-icon-close {
        fill:red;
      }
    }
    p {
      margin-top:0.4em;
      color: #888;
      font-size: 0.8em;
    }
    p.foot {
      font-size:0.6em
    }
  }
}
</style>