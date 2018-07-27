<template>
  <div v-show="show" class="top-alert" :class="type">
    <icon v-if="close" type="clear" class="close" @click.native="show = false"></icon>
    <div class="message">{{message}}</div>
  </div>
</template>

<script>
import { Icon } from 'vux'

export default {
  name: 'topalert',
  props: {
    type: {
      type: String,
      default: 'warn'
    },
    message: {
      type: String,
      default: ''
    },
    close: {
      type: Boolean,
      default: true
    }
  },
  components: {
    Icon
  },
  data () {
    return {
      show: true
    }
  },
  mounted () {
  },
  methods: {
  }
}
</script>

<style lang="less">
.top-alert {
  padding:5px 10px;
  font-size:.9em;
  .message {
    margin-right:25px;
    white-space: nowrap;
    overflow: auto;
  }
  .message::-webkit-scrollbar {
    display: none;
  }
  .close {
    float: right;
    margin-top: 4px;
  }
}
.top-alert.warn {
  background-color: antiquewhite;
}
.top-alert.info {
  background-color:rgb(254, 255, 215);
}
</style>
