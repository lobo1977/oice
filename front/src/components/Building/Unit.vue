<template>
  <div>
    <swiper :auto="true" :loop="true" height="260px"
      :show-dots="images.length > 1" v-show="images.length > 0">
      <swiper-item class="swiper-img" v-for="(item, index) in images" :key="index">
        <img :src="item.src" @click="preview(index)">
      </swiper-item>
    </swiper>

    <div v-transfer-dom>
      <previewer :list="images" ref="previewer" :options="previewOptions"></previewer>
    </div>

    <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
      <cell title="朝向" value-align="left" :value="info.face" v-if="info.face"></cell>
      <cell title="面积" value-align="left" :value="info.acreage + ' 平方米'" v-if="info.acreage"></cell>
      <cell title="租售" value-align="left" :value="info.rent_sell" v-if="info.rent_sell"></cell>
      <cell title="出租价格" value-align="left" :value="info.rent_price + ' 元/平方米/日'" v-if="info.rent_price"></cell>
      <cell title="出售价格" value-align="left" :value="info.sell_price + ' 元/平方米'" v-if="info.sell_price"></cell>
      <cell title="装修状况" value-align="left" :value="info.decoration" v-if="info.decoration"></cell>
      <cell title="状态" value-align="left" :value="info.statusText" v-if="info.statusText"></cell>
      <cell title="到期日" value-align="left" :value="info.end_date" v-if="info.end_date"></cell>
    </group>

    <group title="备注" v-show="info.rem">
      <p class="group-padding">{{info.rem}}</p>
    </group>

    <group>
      <group-title slot="title">联系人
        <router-link style="float:right;color:#333;" 
          :to="{name: 'LinkmanEdit', params: {id: 0, type: 'unit', oid: info.id}}">+ 添加</router-link>
      </group-title>
      <cell v-for="(item, index) in linkman" :key="index"
        :title="item.title" :link="{name: 'Linkman', params: {id: item.id}}" 
        :inline-desc="item.desc"></cell>
    </group>

    <flexbox :gutter="0" class="bottom-bar">
      <flexbox-item :span="6">
        <x-button type="primary" class="bottom-btn" :disabled="info.id === 0"
          @click.native="favorite">
          <x-icon type="star" class="btn-icon"></x-icon> {{favoriteText}}
        </x-button>
      </flexbox-item>
      <flexbox-item>
        <x-button type="warn" class="bottom-btn" :disabled="info.id === 0"
          :link="{name:'UnitEdit', params: {id: info.id, bid: info.building_id}}">
          <x-icon type="compose" class="btn-icon"></x-icon> 修改
        </x-button>
      </flexbox-item>
      <flexbox-item :span="2" v-if="user && info.user_id == user.id">
        <x-button type="default" class="bottom-btn" @click.native="remove">
          <x-icon type="trash-a" class="btn-icon"></x-icon>
        </x-button>
      </flexbox-item>
    </flexbox>
  </div>
</template>

<script>
import { Swiper, SwiperItem, Previewer, TransferDom,
  Group, GroupTitle, Cell, Flexbox, FlexboxItem, XButton, dateFormat } from 'vux'

export default {
  directives: {
    TransferDom
  },
  components: {
    Swiper,
    SwiperItem,
    Previewer,
    Group,
    GroupTitle,
    Cell,
    Flexbox,
    FlexboxItem,
    XButton,
    dateFormat
  },
  data () {
    return {
      user: null,
      info: {
        id: 0,
        title: '',
        building_name: '',
        building_id: 0,
        building_no: '',
        floor: 0,          // 楼层
        room: '',          // 房间号
        face: '',          // 朝向
        acreage: 0,        // 面积
        rent_sell: '',     // 租售
        rent_price: 0,     // 出租价格
        sell_price: 0,     // 出售价格
        decoration: '',    // 装修状况
        status: 0,         // 状态
        statusText: '',
        end_date: '',      // 到日期
        rem: '',           // 备注
        user_id: 0,
        isFavorite: false
      },
      previewOptions: {
      },
      images: [],
      linkman: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.$get('/api/unit/detail?id=' + id, (res) => {
          if (res.success) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            vm.info.title = vm.info.building_name + vm.info.building_no + vm.formatFloor(vm.info.floor) + vm.info.room
            if (vm.info.end_date) {
              vm.info.end_date = vm.info.end_date ? dateFormat(new Date(Date.parse(vm.info.end_date.replace(/-/g, '/'))), 'YYYY年M月D日') : ''
            }
            if (res.data.isFavorite) {
              vm.info.isFavorite = true
            }
            if (res.data.images) {
              vm.images = res.data.images
            }
            if (res.data.linkman) {
              vm.linkman = res.data.linkman
            }
            vm.$emit('on-view-loaded', vm.info.title)
          } else {
            vm.info.id = 0
            vm.$vux.toast.show({
              text: res.message,
              width: '15em'
            })
          }
        })
      } else {
        vm.$emit('on-view-loaded', '单元信息')
      }
    })
  },
  methods: {
    formatFloor (floor) {
      if (floor > 0) {
        return floor + '层'
      } else if (floor < 0) {
        return '地下' + Math.abs(floor) + '层'
      } else {
        return ''
      }
    },
    favorite () {
      if (this.user) {
        this.$vux.loading.show()
        if (this.info.isFavorite) {
          this.$post('/api/unit/unFavorite', {
            id: this.info.id
          }, (res) => {
            this.$vux.loading.hide()
            if (res.success) {
              this.info.isFavorite = false
              this.$vux.toast.show({
                type: 'success',
                text: '已从资料夹移除。',
                width: '13em'
              })
            } else {
              this.$vux.toast.show({
                text: res.message,
                width: '15em'
              })
            }
          })
        } else {
          this.$post('/api/unit/favorite', {
            id: this.info.id
          }, (res) => {
            this.$vux.loading.hide()
            if (res.success) {
              this.info.isFavorite = true
              this.$vux.toast.show({
                type: 'success',
                text: '已添加到资料夹。',
                width: '13em'
              })
            } else {
              this.$vux.toast.show({
                text: res.message,
                width: '15em'
              })
            }
          })
        }
      } else {
        this.$router.push({
          name: 'Login',
          query: { redirect: this.$route.fullPath }
        })
      }
    },
    remove () {
      if (this.user) {
        let vm = this
        this.$vux.confirm.show({
          title: '删除单元',
          content: '确定要删除单元 <strong>' + vm.info.title + '</strong> 吗？',
          onConfirm () {
            vm.$vux.loading.show()
            vm.$post('/api/unit/remove', {
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
    }
  },
  computed: {
    favoriteText () {
      return this.info.isFavorite ? '从资料夹移除' : '添加到资料夹'
    }
  }
}
</script>

<style lang="less">
.swiper-img {
  text-align:center;
  overflow: hidden;
  img {
    margin:0 auto;
    height:260px;
  }
}
</style>