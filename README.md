<h1>SimpleSleep<img src="https://github.com/brokiem/SimpleSleep/blob/master/assets/logo.PNG" height="64" width="64" align="left" alt=""></h1><br>

[![License](https://img.shields.io/github/license/brokiem/SimpleSleep)](https://github.com/brokiem/SimpleSleep)
[![Poggit](https://poggit.pmmp.io/shield.state/SimpleSleep)](https://poggit.pmmp.io/p/SimpleSleep)
[![Poggit](https://poggit.pmmp.io/shield.dl.total/SimpleSleep)](https://poggit.pmmp.io/p/SimpleSleep)
[![Star](https://img.shields.io/github/stars/brokiem/SimpleSleep)](https://github.com/brokiem/SimpleSleep/stargazers) <br>

### Description
This plugin aims to improve sleeping in multiplayer by only having a certain percentage of players sleep.

### Features
- Set minimum players to sleep so that the time is morning
- Custom messages, sleep duration, and minimal players to sleep
- Fully maintained

### Commands
| Command | Description | Permission | Default |
| --- | --- | --- | --- |
| ```/simplesleep reload``` | ```Reload SimpleSleep config``` | ```simplesleep.command``` | op |

### Issues
If you find issues, please create issues [here](https://github.com/brokiem/SimpleSleep/issues/new)

### Config
```yaml
######## SimpleSleep Configuration File ########
# Enabled world for sleep to trigger event
enable-all-worlds: true
# If "enable-all-worlds" is false, set your folder world name below
enabled-worlds:
  - "world"
  - "world2"
# Sleep duration in tick (20 ticks = 1 second)
sleep-duration: 100 # mean 5 seconds
# Minimum players to change time
minimal-players: 1
# Message type (Value: "message", "actionbar")
message-type: "actionbar"
# Sleep Messages
on-enter-bed-message: "{player} is sleeping!"
```