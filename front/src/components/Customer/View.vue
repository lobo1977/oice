<template>
  <div>
    <tab>
      <tab-item @on-item-click="goTab(0)" :selected="tab === 0">基本信息</tab-item>
      <tab-item @on-item-click="goTab(1)" :selected="tab === 1">跟进纪要</tab-item>
      <tab-item @on-item-click="goTab(2)" :selected="tab === 2">项目筛选</tab-item>
      <tab-item @on-item-click="goTab(3)" :selected="tab === 3">推荐资料</tab-item>
    </tab>

    <div v-show="tab === 0">
      <flow>
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

      <group>
        <group-title slot="title">联系人
          <router-link style="float:right;color:#333;" 
            :to="{name: 'LinkmanEdit', params: {id: 0, type: 'customer', oid: info.id}}">+ 添加</router-link>
        </group-title>
        <cell v-for="(item, index) in linkman" :key="index"
          :title="item.title" :link="{name: 'Linkman', params: {id: item.id}}" 
          :inline-desc="item.desc"></cell>
      </group>

      <group v-if="info.user_id > 0" title="客户经理">
        <cell :title="info.manager" :inline-desc="info.company || info.mobile">
          <img slot="icon" :src="info.avatar" class="cell-image">
        </cell>
      </group>

      <flexbox :gutter="0" class="bottom-bar">
        <flexbox-item :span="6">
          <x-button type="primary" class="bottom-btn" :disabled="info.id === 0"
            :link="{name:'Favorite', query: { cid: info.id }}">
            <x-icon type="search" class="btn-icon"></x-icon> 筛选项目
          </x-button>
        </flexbox-item>
        <flexbox-item :span="4">
          <x-button type="warn" class="bottom-btn" :disabled="info.id === 0 || info.user_id != user.id"
            :link="{name:'CustomerEdit', params: { id: info.id }}">
            <x-icon type="compose" class="btn-icon"></x-icon> 修改
          </x-button>
        </flexbox-item>
        <flexbox-item :span="2">
          <x-button type="default" class="bottom-btn" :disabled="info.id === 0 || info.user_id != user.id"
            @click.native="remove">
            <x-icon type="trash-a" class="btn-icon"></x-icon>
          </x-button>
        </flexbox-item>
      </flexbox>
    </div>

    <div v-show="tab === 1" class="time-line">
      <timeline>
        <timeline-item v-for="(item, index) in log" :key="index">
          <h4>
            <span>{{item.title}}</span>
            <span @click="editLog(item.id)">
              <x-icon v-if="item.system === 0 && item.user_id == user.id" 
                type="compose" size="20"></x-icon>
            </span>
            <span @click="removeLog(item.id)">
              <x-icon v-if="item.system === 0 && item.user_id == user.id" 
                type="android-cancel" size="20"></x-icon>
            </span>
          </h4>
          <p v-if="item.summary" v-html="item.summary"></p>
          <p>{{item.create_time}}</p>
          <p v-if="item.user || item.mobile">{{item.user || item.mobile}}</p>
        </timeline-item>
      </timeline>

      <div class="bottom-bar">
        <x-button type="warn" class="bottom-btn" :disabled="info.id === 0"
          :link="{name: 'CustomerLog', params: {id:0, cid: info.id}}">
          <x-icon type="plus" class="btn-icon"></x-icon> 添加
        </x-button>
      </div>
    </div>

    <div v-show="tab === 2">
      <group :gutter="0">
        <swipeout>
          <swipeout-item v-for="(item, index) in filter" :key="index" transition-mode="follow">
            <div slot="right-menu">
              <swipeout-button v-if="index > 0" @click.native="sortFilter(item, true)" type="primary">上移</swipeout-button>
              <swipeout-button v-if="index < filter.length - 1" @click.native="sortFilter(item, false)" type="default">下移</swipeout-button>
              <swipeout-button @click.native="removeFilter(item)" type="warn">删除</swipeout-button>
            </div>
            <cell slot="content" :title="item.title">
              <p slot="inline-desc" class="cell-desc">{{item.desc}}</p>
              <div solt="default">
                <check-icon :value.sync="item.checked" @update:value="check"></check-icon>
              </div>
            </cell>
          </swipeout-item>
        </swipeout>
      </group>

      <actionsheet v-model="showPrintPicker" :menus="printMode" theme="android" @on-click-menu="choseMode"></actionsheet>

      <flexbox :gutter="0" class="bottom-bar">
        <flexbox-item :span="6">
          <x-button type="primary" class="bottom-btn" :disabled="info.id === 0"
            :link="{name:'Favorite', query: { cid: info.id }}">
            <x-icon type="plus" class="btn-icon"></x-icon> 添加筛选
          </x-button>
        </flexbox-item>
        <flexbox-item>
          <x-button type="warn" class="bottom-btn" @click.native="newRecommend" 
            :disabled="info.id === 0 || checkCount <= 0">
            <x-icon type="share" class="btn-icon"></x-icon> 生成推荐资料
          </x-button>
        </flexbox-item>
      </flexbox>
    </div>

    <div v-show="tab === 3">
      <group :gutter="0">
        <swipeout>
          <swipeout-item v-for="(item, index) in recommend" :key="index"
            @on-move="swipeoutOpen(item)" @on-end="swipeoutClose(item)"
            transition-mode="follow">
            <div slot="right-menu">
              <swipeout-button @click.native="removeRecommend(item.id)" type="warn">删除</swipeout-button>
            </div>
            <cell slot="content" :disabled="item.disabled" is-link @click.native="print(item)">
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
import { Tab, TabItem, Flow, FlowState, FlowLine, Group, GroupTitle, Cell, Panel,
  Swipeout, SwipeoutItem, SwipeoutButton, CheckIcon, Actionsheet,
  Flexbox, FlexboxItem, XButton, dateFormat, Timeline, TimelineItem } from 'vux'
import printModeData from '../../data/print_mode.json'

export default {
  components: {
    Tab,
    TabItem,
    Flow,
    FlowState,
    FlowLine,
    Group,
    GroupTitle,
    Cell,
    Panel,
    Swipeout,
    SwipeoutItem,
    SwipeoutButton,
    CheckIcon,
    Actionsheet,
    Flexbox,
    FlexboxItem,
    XButton,
    Timeline,
    TimelineItem
  },
  data () {
    return {
      tab: 0,
      user: null,
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
        user_id: 0,
        manager: '',          // 客户经理
        avatar: '/static/img/avatar.png',
        mobile: '',
        company: ''           // 所属企业
      },
      linkman: [],            // 联系人
      log: [],                // 跟进纪要
      filter: [],             // 项目筛选表
      recommend: [],          // 推荐资料
      checkCount: 0,
      showPrintPicker: false,
      printMode: printModeData
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user
      if (to.query.tab) {
        vm.tab = parseInt(to.query.tab)
        if (isNaN(vm.tab)) {
          vm.tab = 0
        }
      }
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.$get('/api/Customer/detail?id=' + id, (res) => {
          if (res.success) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            if (res.data.linkman) {
              vm.linkman = res.data.linkman
            }
            if (res.data.log) {
              vm.log = res.data.log
            }
            if (res.data.filter) {
              vm.filter = res.data.filter
            }
            if (res.data.recommend) {
              vm.recommend = res.data.recommend
            }
            vm.$emit('on-view-loaded', vm.info.customer_name)
          } else {
            vm.info.id = 0
            vm.$vux.toast.show({
              text: res.message,
              width: '15em'
            })
          }
        })
      } else {
        vm.tab = 0
      }
    })
  },
  methods: {
    goTab (tab) {
      this.$router.replace({name: 'CustomerView', id: this.info.id, query: { tab: tab }})
      this.tab = tab
    },
    remove () {
      if (this.user) {
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
      } else {
        this.$router.push({
          name: 'Login',
          query: { redirect: this.$route.fullPath }
        })
      }
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
              for (let i in vm.log) {
                if (vm.log[i].id === id) {
                  vm.log.splice(i, 1)
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
      this.filter.forEach((item, index) => {
        if (item.checked) {
          selectedList.push('' + item.building_id + ',' + item.unit_id)
        }
      })
      return selectedList
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
          this.filter = res.data
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
          this.filter = res.data
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '13em'
          })
        }
      })
    },
    newRecommend () {
      let vm = this
      if (vm.checkCount <= 0) {
        vm.$vux.toast.show({
          text: '请选择要推荐的项目。',
          width: '15em'
        })
      } else {
        this.showPrintPicker = true
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
          vm.recommend = res.data
          vm.goTab(3)
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '15em'
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
      if (item.disabled) return
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
              for (let i = vm.recommend.length - 1; i >= 0; i--) {
                if (vm.recommend[i].id === id) {
                  vm.recommend.splice(i, 1)
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
    formatDate (value) {
      if (value) {
        return dateFormat(new Date(Date.parse(value.replace(/-/g, '/'))), 'YYYY年M月D日')
      } else {
        return ''
      }
    },
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
	p {
    margin-top:0.2em;
		color: #888;
		font-size: 0.8rem;
	}
	h4 {
		color: #666;
    font-weight: normal;
    .vux-x-icon {
      position: relative;
      top:4px;
      margin-left:5px;
      cursor:pointer;
    } 
    .vux-x-icon-compose {
      fill:rgb(4, 190, 2);
    }
    .vux-x-icon-android-cancel {
      fill:red;
    }
	}
	.recent {
		color: rgb(4, 190, 2)
  }
  .vux-timeline-item-head{
    top:8px;
  }
}
</style>