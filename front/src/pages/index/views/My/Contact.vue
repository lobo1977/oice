<template>
  <div>
    <group :gutter="0">
      <swipeout>
        <swipeout-item v-for="(item, index) in list" :key="index" transition-mode="follow"
          @on-open="swipeoutOpen(item)" @on-close="swipeoutClose(item)"
          @mousedown.native="itemMouseDown" @mouseup.native="itemMouseUp" 
          @touchstart.native="itemMouseDown" @touchend.native="itemMouseUp"
          @click.native="itemClick(item)">
          <div slot="right-menu">
            <swipeout-button v-if="!item.is_colleague" @click.native.stop="remove(item)" type="warn">移除</swipeout-button>
          </div>
          <cell slot="content" :title="item.title">
            <img slot="icon" :src="item.avatar" class="cell-image">
            <div slot="inline-desc">
              <span>{{item.full_name}}</span>
            </div>
          </cell>
        </swipeout-item>
      </swipeout>
    </group>

    <div style="height:50px;">
      <load-more :show-loading="isLoading" v-show="isLoading || isEnd" :tip="loadingTip"></load-more>
    </div>
  </div>
</template>

<script>
import { Swipeout, SwipeoutItem, SwipeoutButton } from 'vux'
import { mapState } from 'vuex'

export default {
  components: {
    Swipeout,
    SwipeoutItem,
    SwipeoutButton
  },
  data () {
    return {
      user: {
      },
      isLoading: false,
      page: 0,
      isEnd: false,
      list: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user || vm.user
      vm.loadListData(true)
    })
  },
  methods: {
    loadListData (empty) {
      if (empty) {
        this.isEnd = false
        this.page = 1
        this.list = []
      }
      this.isLoading = true
      this.$post('/api/my/contact', {
        page: this.page
      }, (res) => {
        this.isLoading = false
        if (res.success) {
          let newData = res.data
          if (!newData || newData.length < 10) {
            this.isEnd = true
          }
          for (let item in newData) {
            this.list.push(newData[item])
          }
        }
      })
    },
    swipeoutOpen (item) {
      item.disabled = true
    },
    swipeoutClose (item) {
      item.disabled = false
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
      if (item.disabled || this.mouseMove) {
      } else {
        this.$router.push({name: 'UserView', params: {id: item.id}})
      }
    },
    remove (user) {
      let vm = this
      if (this.user) {
        this.$vux.confirm.show({
          title: '移除联系人',
          content: '确定要移除联系人 <strong>' + user.title + '</strong> 吗？',
          onConfirm () {
            vm.$vux.loading.show()
            vm.$post('/api/my/removeContact', {
              contact_id: user.id
            }, (res) => {
              vm.$vux.loading.hide()
              if (res.success) {
                vm.loadListData(true)
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
    }
  },
  watch: {
    scrollBottom (isBottom) {
      if (isBottom && this.$route.name === 'Contact' &&
        !this.isLoading && !this.isEnd) {
        this.page++
        this.loadListData()
      }
    }
  },
  computed: {
    ...mapState({
      scroolTop: state => state.oice.scroolTop,
      scrollBottom: state => state.oice.scrollBottom
    }),
    loadingTip () {
      if (this.isLoading) {
        return '正在加载'
      } else if (this.isEnd) {
        return this.list.length ? '没有更多了' : '暂无数据'
      } else {
        return ''
      }
    }
  }
}
</script>

<style lang="less">
  .cell-desc-icon {
    fill: rgb(4, 190, 2);
  }
</style>