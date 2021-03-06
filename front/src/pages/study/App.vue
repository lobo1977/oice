<template>
  <div :style="{'padding-top': paddingTop, 'padding-bottom': paddingBottom}">
    <x-header slot="header"
      style="width:100%;position:fixed;left:0;top:0;z-index:100;"
      :left-options="leftOptions"
      :right-options="rightOptions"
      :title="title"
      :transition="headerTransition" @on-click-back="goBack">
      <span slot="right" v-show="$route.meta.showEdit" @click="goEdit">
        <x-icon type="compose" size="24" class="header-icon"></x-icon>
      </span>
      <span slot="right" v-show="$route.meta.showPrint" @click="goPrint">
        <x-icon type="ios-printer" size="24" class="header-icon"></x-icon>
      </span>
      <span slot="right" v-show="$route.meta.showMenu" @click="showMenu">
        <x-icon type="ios-more" size="24" class="header-icon"></x-icon>
      </span>
    </x-header>

    <keep-alive>
      <router-view ref="page" v-if="$route.meta.keepAlive" @on-component-mounted="componentMounted" 
        @on-view-loaded="changeTitle"></router-view>
    </keep-alive>
    <router-view ref="page" v-if="!$route.meta.keepAlive" @on-component-mounted="componentMounted" 
        @on-view-loaded="changeTitle"></router-view>

    <tabbar style="position:fixed;" icon-class="vux-center" v-if="!this.$route.meta.hideBar" slot="bottom">
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
    showMenu () {
      if (this.$refs.page && this.$refs.page.propMenu) {
        this.$refs.page.propMenu()
      }
    },
    goEdit () {
      if (this.$refs.page && this.$refs.page.edit) {
        this.$refs.page.edit()
      }
    },
    goPrint () {
      if (this.$refs.page && this.$refs.page.print) {
        this.$refs.page.print()
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
    },
    paddingTop () {
      return '46px'
    },
    paddingBottom () {
      return '0'
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
    margin-left:10px;
    fill:#fff;
    position:relative;
    top:-3px;
    cursor:pointer;
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

.bottom-bar {
  position:fixed;
  bottom:0;
  width:100%;
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

.vux-group-footer-title {
  margin-top:0.5em !important;
}
</style>
