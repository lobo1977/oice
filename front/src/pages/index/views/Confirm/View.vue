<template>
  <div>
      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="项目" value-align="left" :value="info.building" 
          :link="{name: 'BuildingView', params: { id: info.building_id }}"></cell>
        <cell title="委托方" value-align="left">
          <span v-if="info.developer">{{info.developer}}</span>
          <span v-if="info.id > 0 && !info.developer" style="color:red">项目缺少开发商信息</span>
        </cell>
        <cell title="代理方" :value="info.company" value-align="left"></cell>
        <cell title="客户" value-align="left" :value="info.customer"
          :link="{name: 'CustomerView', params: { id: info.customer_id }}"></cell>
        <cell title="面积" value-align="left" :value="info.acreage + ' 平方米'" v-if="info.acreage"></cell>
        <cell title="租售" value-align="left" :value="info.rent_sell" v-if="info.rent_sell"></cell>
        <cell title="确认日期" value-align="left" :value="info.confirm_date|formatDate" v-if="info.confirm_date"></cell>
        <cell title="截止日期" value-align="left" :value="info.end_date|formatDate" v-if="info.end_date"></cell>
        <cell title="确认书" value-align="left" value="点击下载" :is-link="true" @click.native="download"></cell>
      </group>

      <group title="备注" v-show="info.rem">
        <p class="group-padding">{{info.rem}}</p>
      </group>

      <group v-if="info.user_id" title="客户经理">
        <cell :title="info.manager" :inline-desc="info.mobile"
          :link="{name: 'UserView', params: {id: info.user_id}}">
          <img slot="icon" :src="info.avatar" class="cell-image">
        </cell>
      </group>

      <div class="bottom-bar">
        <x-button type="primary" class="bottom-btn"
          :link="{name:'ConfirmEdit', params: { id: info.id, bid: 0, cid: 0 }}"
          :disabled="!info.allowEdit">
          <x-icon type="compose" class="btn-icon"></x-icon> 修改
        </x-button>
      </div>
  </div>
</template>

<script>
export default {
  components: {
  },
  data () {
    return {
      info: {
        id: '',
        building_id: 0, // 项目
        building: '',
        developer: '',
        customer_id: 0,
        customer: '',
        acreage: null, // 面积
        rent_sell: '', // 租售
        confirm_date: null, // 确认日期
        end_date: null, // 截止日期
        rem: '', // 备注
        company: '',
        user_id: 0,
        manager: '',
        avatar: '',
        mobile: '',
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

      vm.$get('/api/confirm/detail?id=' + vm.id, res => {
        if (res.success) {
          for (let item in vm.info) {
            if (res.data[item] !== undefined && res.data[item] !== null) {
              vm.info[item] = res.data[item]
            }
          }

          if (!vm.info.acreage || vm.info.acreage === '0') {
            vm.info.acreage = null
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
    download () {
      this.$vux.loading.show()
      this.$get('/api/confirm/pdf?id=' + this.id, res => {
        this.$vux.loading.hide()
        if (res.success) {
          document.location.href = res.data
        } else {
          this.info.__token__ = res.data
          this.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    }
  }
}
</script>

<style lang="less">
</style>