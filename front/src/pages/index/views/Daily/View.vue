<template>
  <div>
    <group gutter="0" label-width="4em" label-margin-right="1em" label-align="left">
      <cell title="开始时间" :value="info.start_time" value-align="left"></cell>
      <cell title="结束时间" :value="info.end_time" value-align="left" v-show="info.end_time.length" ></cell>
      <cell title="摘要" :value="info.title" value-align="left" v-show="info.title.length"></cell>
    </group>
    <group title="详情" v-show="info.summary.length">
      <p class="group-padding">{{info.summary}}</p>
    </group>
    <group title="提交人" v-show="info.user_id">
      <cell :title="info.username" :inline-desc="info.mobile"
        :link="{name: 'UserView', params: {id: info.user_id}}">
        <img slot="icon" :src="info.avatar" class="cell-image">
      </cell>
    </group>

    <flexbox :gutter="0" class="bottom-bar">
      <flexbox-item :span="6">
        <x-button type="warn" class="bottom-btn" :disabled="!info.allowEdit"
          :link="{name:'DailyEdit', params: { id: id }}">
          <x-icon type="compose" class="btn-icon"></x-icon> 修改
        </x-button>
      </flexbox-item>
      <flexbox-item>
        <x-button type="default" class="bottom-btn" :disabled="!info.allowDelete"
          @click.native="remove">
          <x-icon type="trash-a" class="btn-icon"></x-icon> 删除
        </x-button>
      </flexbox-item>
    </flexbox>
  </div>
</template>

<script>
export default {
  components: {
  },
  data () {
    return {
      id: 0,
      info: {
        level: 0,
        title: '',        // 摘要
        summary: '',      // 详情
        start_time: '',   // 时间
        end_time: '',
        user_id: 0,
        username: '',
        mobile: '',
        avatar: '',
        allowEdit: false,
        allowDelete: false
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (to.params.id) {
        vm.id = parseInt(to.params.id)
        if (isNaN(vm.id)) {
          vm.id = 0
        }
      }

      vm.$get('/api/daily/detail?id=' + vm.id, (res) => {
        if (res.success) {
          if (vm.id) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            vm.$emit('on-view-loaded', '工作日报')
          }
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    })
  },
  methods: {
    remove () {
      let vm = this
      vm.$vux.confirm.show({
        title: '删除工作日报',
        content: '确定要删除条工作日报吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/daily/remove', {
            id: vm.id
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
    }
  }
}
</script>

<style lang="less">
</style>