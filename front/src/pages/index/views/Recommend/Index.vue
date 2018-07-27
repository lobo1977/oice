<template>
  <div>
    <group>
      <cell v-for="(item, index) in list" :key="index" :title="item.title" :link="item.url">
        <img slot="icon" :src="item.src" class="cell-image" />
        <p slot="inline-desc" class="cell-desc">{{item.desc}}</p>
      </cell>
    </group>
    
    <div class="bottom-bar">
      <x-button type="warn" class="bottom-btn" :disabled="id === 0"
        :link="{name: 'RecommendView', params: {id: id}}">
        <x-icon type="android-send" class="btn-icon"></x-icon> 预览
      </x-button>
    </div>
  </div>
</template>

<script>
import { Group, Cell, XButton } from 'vux'

export default {
  components: {
    Group,
    Cell,
    XButton
  },
  data () {
    return {
      id: 0,
      list: []
    }
  },
  methods: {
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (to.params.id) {
        vm.id = parseInt(to.params.id)
        if (!isNaN(vm.id)) {
          vm.$get('/api/customer/show?id=' + vm.id, (res) => {
            if (res.success) {
              vm.list = res.data
            }
          })
        }
      }
    })
  }
}
</script>