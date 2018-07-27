<template>
  <div>
    <msg icon="warn" title="页面不存在" 
        description="您输入了错误的地址或访问的页面已被删除。">
      <template slot="buttons">
        <x-button plain type="primary" @click.native="back">返回</x-button>
      </template>
    </msg>
  </div>
</template>

<script>
import { Msg, XButton } from 'vux'
export default {
  components: {
    Msg,
    XButton
  },
  methods: {
    back () {
      this.$router.push({name: 'Home'})
    }
  }
}
</script>