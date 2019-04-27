<template>
  <div :style="{'padding-top': paddingTop, 'padding-bottom': paddingBottom}">
    <x-header slot="header"
      style="width:100%;position:fixed;left:0;top:0;z-index:100;"
      :left-options="leftOptions"
      :right-options="rightOptions"
      :title="title"
      :transition="headerTransition" @on-click-back="goBack">
      <span slot="right" v-show="$route.meta.showOutput" @click="callExport" style="margin-right:10px;">
        <x-icon type="android-download" size="24" class="header-icon"></x-icon>
      </span>
      <span slot="right" v-if="$route.meta.showSearch" @click="callSearch" style="margin-right:10px;">
        <x-icon type="search" size="24" class="header-icon"></x-icon>
      </span>
      <span slot="right" v-if="$route.meta.robotShare" @click="callShare" style="margin-right:10px;">
        <x-icon type="android-share" size="24" class="header-icon"></x-icon>
      </span>
      <span slot="right" v-if="$route.meta.showPlus" @click="callNew">
        <x-icon type="plus" size="24" class="header-icon"></x-icon>
      </span>
    </x-header>

    <keep-alive>
      <router-view ref="page" v-if="$route.meta.keepAlive && !$route.query.reload" @on-component-mounted="componentMounted" 
        @on-view-loaded="changeTitle"></router-view>
    </keep-alive>
    <router-view ref="page" v-if="!$route.meta.keepAlive || $route.query.reload" @on-component-mounted="componentMounted" 
        @on-view-loaded="changeTitle"></router-view>

    <tabbar style="position:fixed;" icon-class="vux-center" v-if="!this.$route.meta.hideBar" slot="bottom">
      <tabbar-item :selected="$route.path.indexOf('/building') === 0" link="/building">
        <x-icon slot="icon" type="home" size="30"></x-icon>
        <span slot="label">项目</span>
      </tabbar-item>
      <tabbar-item :selected="$route.path.indexOf('/customer') === 0" link="/customer">
        <x-icon slot="icon" type="android-contacts" size="30"></x-icon>
        <span slot="label">客户</span>
      </tabbar-item>
      <tabbar-item :selected="$route.path.indexOf('/daily') === 0" link="/daily">
        <x-icon slot="icon" type="android-calendar" size="30"></x-icon>
        <span slot="label">日报</span>
      </tabbar-item>
      <tabbar-item :selected="$route.path.indexOf('/my') === 0" :show-dot="user && user.invite_me > 0" link="/my">
        <x-icon slot="icon" type="person" size="30"></x-icon>
        <span slot="label">我的</span>
      </tabbar-item>
    </tabbar>
  </div>
</template>

<script>
import { XHeader, Tabbar, TabbarItem } from 'vux'
import { mapState, mapActions } from 'vuex'

export default {
  name: 'app',
  components: {
    XHeader,
    Tabbar,
    TabbarItem
  },
  data () {
    return {
      title: document.title
    }
  },
  methods: {
    ...mapActions([
      'updateScrollPosition'
    ]),
    goBack () {
      if (this.$route.query.index === 'true' ||
        window.history.length <= 1) {
        this.$router.push({name: 'Home'})
      } else {
        this.$router.back()
      }
    },
    callExport () {
      if (this.$refs.page && this.$refs.page.export) {
        this.$refs.page.export()
      }
    },
    callNew () {
      if (this.$refs.page && this.$refs.page.new) {
        this.$refs.page.new()
      }
    },
    callShare () {
      if (this.$refs.page && this.$refs.page.share) {
        this.$refs.page.share()
      }
    },
    callSearch () {
      if (this.$refs.page && this.$refs.page.search) {
        this.$refs.page.search()
      }
    },
    changeTitle (title) {
      if (title) {
        this.title = title
      }
    },
    componentMounted (componentName) {
    }
  },
  computed: {
    ...mapState({
      user: state => state.oice.user,
      isLoading: state => state.oice.isLoading,
      direction: state => state.oice.direction,
      scrollTop: state => state.oice.scrollTop
    }),
    leftOptions () {
      return {
        preventGoBack: true,
        showBack: this.$route.meta.showBack
      }
    },
    rightOptions () {
      return {
        showMore: this.$route.meta.showMore
      }
    },
    headerTransition () {
      return ''
      // if (!this.direction) return ''
      // return this.direction === 'forward' ? 'vux-header-fade-in-right' : 'vux-header-fade-in-left'
    },
    paddingTop () {
      return '46px'
    },
    paddingBottom () {
      return '55px'
    }
  },
  created () {
  },
  mounted () {
    if (this.$route.meta.title) {
      this.title = this.$route.meta.title
    }

    this.scrollHandler = () => {
      let isBottom = false
      let scrollBox = document.body || document.documentElement
      let clientHeight = window.screen.availHeight
      let scrollHeight = scrollBox.scrollHeight
      let scrollTop = window.pageYOffset ? window.pageYOffset : scrollBox.scrollTop

      if (scrollHeight > clientHeight) {
        if (scrollTop + clientHeight >= scrollHeight - 5) {
          isBottom = true
        }
      }

      this.updateScrollPosition({path: this.$route.path, top: scrollTop, isBottom: isBottom})
    }

    setTimeout(() => {
      let scrollBox = window
      scrollBox.removeEventListener('scroll', this.scrollHandler, false)
      scrollBox.addEventListener('scroll', this.scrollHandler, false)
    }, 100)
  },
  beforeDestroy () {
    window.removeEventListener('scroll', this.scrollHandler, false)
  },
  watch: {
    $route (to, from) {
      if (to.meta.title) {
        this.title = to.meta.title
      }
      setTimeout(() => {
        if (to.meta.keepAlive && this.scrollTop[to.path]) {
          window.scrollTo(0, this.scrollTop[to.path])
        } else {
          window.scrollTo(0, 0)
        }
      }, 10)
    },
    isLoading (loading) {
      if (loading) {
        this.$vux.loading.show()
      } else {
        this.$vux.loading.hide()
      }
    }
  }
}
</script>

<style lang="less">
@import '~vux/src/styles/reset.less';
@import '~vux/src/styles/1px.less';

html, body {
  background-color: #fbf9fe;
}

.green {
  fill: #09bb07;
}

.vux-header {
  .header-icon {
    fill:#fff;
    position:relative;
    top:-3px;
  }
}

.weui-tabbar {
  .vux-center {
    left:-2px;
  }
  .vux-x-icon {
    fill: #888;
  }
  .weui-bar__item_on .vux-x-icon {
    fill: #09BB07;
  }
}

.popup-picker:before {
  width:auto !important;
  border-top:0 !important;
}

.pswp__caption__center {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-align:center !important; 
}

.weui-btn {
  .btn-icon {
    position: relative;
    top: 4px;
    width: 22px;
    height: 22px;
    fill: #1aad19;
  }
}

.bottom-bar {
  position:fixed;
  bottom:0;
  width:100%;
  z-index:100;
  .weui-btn {
    border:0;
    border-radius:0;
    white-space: nowrap;
    text-overflow: ellipsis;
    font-size:0.9em;
    .btn-icon {
      position: relative;
      top: 5px;
      width: 20px;
      height: 20px;
    }
  }
  .weui-btn:after {
    border:0;
    border-radius:0;
  }
  .weui-btn_warn .btn-icon, 
  .weui-btn_primary .btn-icon {
    fill:#fff;
  }
  .weui-btn_disabled .btn-icon {
    fill:rgba(255, 255, 255, 0.6);
  }
  .weui-btn_disabled.weui-btn_default .btn-icon {
    fill:rgba(0, 0, 0, 0.3);
  }
}

.weui-media-box {
  padding: 6px 10px !important;
  .weui-media-box_appmsg .weui-media-box__thumb {
    height: 60px;
  }
}

.group-padding {
  padding:8px 15px;
  font-size:0.9em;
  color:#999;
  line-height: 1.6em;
}

.cell-icon {
  fill: #09bb07;
  position: relative;
  top: 4px;
  margin-right: 8px;
}

.cell-image {
  display:block;
  margin-right:10px;
  width:60px;
  max-height:60px;
}

.cell-desc-icon {
  position: relative;
  top:3px;
  fill:#666;
}

.cell-badge {
  position: relative;
  top: -2px;
  right:5px;
}

.cell-link {
  text-decoration: none;
}

.vux-group-footer-title {
  margin-top:0.5em !important;
}
</style>
