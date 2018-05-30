<template>
  <div>
    <search @result-click="resultClick"
      @on-change="getResult"
      :results="results"
      position="absolute"
      auto-scroll-to-top top="46px"
      @on-focus="onFocus"
      @on-cancel="onCancel"
      @on-submit="onSubmit"
      placeholder="查找并加入企业"
      ref="search"></search>

    <div v-show="!isSearching">
      <group v-if="my.length > 0" title="切换企业">
        <swipeout>
          <swipeout-item v-for="(item, index) in my" :key="index" transition-mode="follow">
            <div slot="right-menu">
              <swipeout-button v-if="user && item.id != user.company_id" @click.native="setDefault(item.id)" type="primary">切换</swipeout-button>
              <swipeout-button @click.native="view(item.id)" type="default">查看</swipeout-button>
              <swipeout-button @click.native="quit(item.id, item.title)" type="warn">退出</swipeout-button>
            </div>
            <div slot="content" class="vux-1px-t swipeout-item">
              {{item.title}}
              <x-icon v-if="user && item.id == user.company_id" type="checkmark-round" class="swipeout-icon"></x-icon>
            </div>
          </swipeout-item>
        </swipeout>
      </group>

      <group v-if="waites.length > 0 || inviteMe.length > 0" title="待加入企业" style="margin-top:1em;">
        <cell v-for="(item, index) in waites" :key="index" 
          :title="item.title" :link="{name:'CompanyView', params:{id: item.id}}" is-link>
          <img slot="icon" :src="item.logo" class="cell-image" />
        </cell>
        <cell v-for="(item, index) in inviteMe" :key="index" 
          :title="item.title" :link="{name:'CompanyView', params:{id: item.id}}" is-link>
          <img slot="icon" :src="item.logo" class="cell-image" />
        </cell>
      </group>

      <group v-if="creates.length > 0" title="我创建的企业" style="margin-top:1em;">
        <cell v-for="(item, index) in creates" :key="index" 
          :title="item.title" :link="{name:'CompanyView', params:{id: item.id}}" is-link>
          <img slot="icon" :src="item.logo" class="cell-image" />
          <div slot="inline-desc">
            <x-icon type="eye" v-if="item.status === 1" size="18" class="cell-desc-icon"></x-icon>
            <x-icon type="eye-disabled" v-if="item.status === 0" size="18" class="cell-desc-icon"></x-icon>
            &nbsp;
            <x-icon type="ios-people" size="18" class="cell-desc-icon"></x-icon>
            <span>{{item.addin}}</span>
          </div>
          <div class="cell-badge" v-if="item.wait > 0">
            <badge :text="item.wait"></badge>
          </div>
        </cell>
      </group>
      
      <div class="bottom-bar">
        <x-button type="warn" class="bottom-btn"
          :link="{name: 'CompanyEdit', params: {id:0}}">
          <x-icon type="plus" class="btn-icon"></x-icon> 创建企业
        </x-button>
      </div>
    </div>
  </div>
</template>

<script>
import { Search, Group, Swipeout, SwipeoutItem, SwipeoutButton, Radio, Cell, Badge, Flexbox, FlexboxItem, XButton } from 'vux'
import { mapState, mapActions } from 'vuex'

export default {
  components: {
    Search,
    Group,
    Swipeout,
    SwipeoutItem,
    SwipeoutButton,
    Radio,
    Cell,
    Badge,
    Flexbox,
    FlexboxItem,
    XButton
  },
  data () {
    return {
      isSearching: false,
      results: [],
      my: [],
      waites: [],
      inviteMe: [],
      creates: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.loadListData()
    })
  },
  methods: {
    ...mapActions([
      'setUser'
    ]),
    // Searcher
    resultClick (item) {
      this.$router.push({name: 'CompanyView', params: {id: item.id}})
      this.$refs.search.setBlur()
      this.isSearching = false
    },
    getResult (val) {
      if (val) {
        this.$post('/api/company/search', {
          keyword: val
        }, (res) => {
          if (res.success) {
            this.results = res.data
          } else {
            this.results = []
          }
        })
      } else {
        this.results = []
      }
    },
    onFocus () {
      this.isSearching = true
    },
    onSubmit () {
      this.$refs.search.setBlur()
      this.isSearching = false
    },
    onCancel () {
      this.isSearching = false
    },
    loadListData () {
      this.$vux.loading.show()
      this.my = []
      this.$get('/api/company', (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          if (res.data.my) {
            this.my = res.data.my
          }
          if (res.data.myWait) {
            this.waites = res.data.myWait
          }
          if (res.data.inviteMe) {
            this.inviteMe = res.data.inviteMe
          }
          if (res.data.myCreate) {
            this.creates = res.data.myCreate
          }
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    setDefault (value) {
      this.$vux.loading.show()
      this.$post('/api/company/setDefault', {
        id: value
      }, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          if (res.data) {
            this.setUser(res.data)
          }
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    view (id) {
      this.$router.push({
        name: 'CompanyView',
        params: {id: id}
      })
    },
    quit (id, title) {
      let vm = this
      this.$vux.confirm.show({
        title: '退出企业',
        content: '确定要退出企业 <strong>' + title + '</strong> 吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/company/quit', {
            id: id
          }, (res) => {
            if (res.success) {
              if (res.data) {
                vm.setUser(res.data)
              }
              vm.loadListData()
              vm.$vux.loading.hide()
              vm.$vux.alert.show({
                title: '提示',
                content: '您已退出 <strong> ' + title + '</strong>。'
              })
            } else {
              vm.$vux.loading.hide()
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
  computed: {
    ...mapState({
      user: state => state.oice.user
    })
  }
}
</script>

<style lang="less">
  .swipeout-item {
    padding: 10px 15px;
    .vux-x-icon {
      position: absolute;
      right:15px;
      fill: rgb(4, 190, 2);
    }
  }
  .cell-desc-icon {
    fill: rgb(4, 190, 2);
  }
  .cell-desc-icon.vux-x-icon-eye-disabled {
    fill: #999;
  }
</style>