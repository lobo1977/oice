<template>
  <div>
    <div style="margin:10px;text-align:center;">
      <h3 style="color:#999;">{{info.title}}</h3>
      <p style="margin-top:10px;font-size:0.85em;color:#999;">{{info.desc}}</p>
      <div v-if="info.cover && info.cover.length > 0" style="margin:15px 0;">
        <img :src="info.cover" style="max-width:100%" />
      </div>
    </div>

    <div v-html="info.content" style="margin:0 20px;" class="article-content">
    </div>

    <flexbox v-if="info.allowEdit || info.allowDelete" :gutter="0" class="bottom-bar">
      <flexbox-item :span="5">
        <x-button type="warn" class="bottom-btn" @click.native="setTop" :disabled="!info.allowEdit">
          <x-icon type="arrow-up-b" class="btn-icon"></x-icon> {{info.top == 1 ? '取消顶置' : '顶置'}}
        </x-button>
      </flexbox-item>
      <flexbox-item :span="5">
        <x-button type="primary" class="bottom-btn" 
          :link="{name: 'ArticleEdit', params: {id: info.id}}" :disabled="!info.allowEdit">
          <x-icon type="compose" class="btn-icon"></x-icon> 修改
        </x-button>
      </flexbox-item>
      <flexbox-item :span="2">
        <x-button type="default" class="bottom-btn" @click.native="remove" :disabled="!info.allowDelete">
          <x-icon type="trash-a" class="btn-icon btn-warn"></x-icon>
        </x-button>
      </flexbox-item>
    </flexbox>
  </div>
</template>

<script>
import { Masker, TransferDom, XDialog } from 'vux'
import { mapActions } from 'vuex'
import 'quill/dist/quill.snow.css'

export default {
  directives: {
    TransferDom
  },
  components: {
    Masker,
    XDialog
  },
  data () {
    return {
      info: {
        id: 0,
        title: '',
        desc: '',
        cover: '',
        summary: '',
        content: '',
        status: 0,
        top: 0,
        allowEdit: false,
        allowDelete: false
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.getInfo(id)
      }
    })
  },
  methods: {
    ...mapActions([
      'getUser'
    ]),
    getInfo (id) {
      let vm = this
      vm.$get('/api/article/detail?id=' + id, (res) => {
        if (res.success) {
          for (let item in vm.info) {
            if (res.data[item] !== undefined && res.data[item] !== null) {
              vm.info[item] = res.data[item]
            }
          }

          vm.$emit('on-view-loaded', vm.info.title)

          if (vm.$isWechat()) {
            let shareLink = window.location.href
            let shareImage = null
            if (vm.info.cover) {
              shareImage = window.location.protocol + '//' +
                window.location.host + vm.info.cover
            }

            vm.$wechatShare(null, shareLink, vm.info.title, vm.info.summary, shareImage)
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
    setTop () {
      let vm = this
      vm.$vux.loading.show()
      vm.$post('/api/article/top', {
        id: vm.info.id,
        top: (vm.info.top === 1 ? 0 : 1)
      }, (res) => {
        vm.$vux.loading.hide()
        if (res.success) {
          vm.info.top = (vm.info.top === 1 ? 0 : 1)
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    remove () {
      let vm = this
      this.$vux.confirm.show({
        title: '删除文章',
        content: '确定要删除文章 <strong>' + vm.info.title + '</strong> 吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/article/remove', {
            id: vm.info.id
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              vm.$router.back()
            } else {
              vm.$vux.toast.show({
                text: res.message,
                width: '15em'
              })
            }
          })
        }
      })
    }
  }
}
</script>

<style lang="less">
.article-content img {
  max-width:100%;
}
.btn-warn {
  fill: #CE3C39 !important;
}
</style>