SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `chat_messages` (
  `ID` int NOT NULL,
  `time` int NOT NULL,
  `message` varchar(256) NOT NULL,
  `sender` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `copycats` (
  `ID` int NOT NULL,
  `owner` int NOT NULL,
  `lvl` smallint NOT NULL DEFAULT '150',
  `class` tinyint NOT NULL,
  `str` mediumint NOT NULL,
  `dex` mediumint NOT NULL,
  `intel` mediumint NOT NULL,
  `wit` mediumint NOT NULL,
  `luck` mediumint NOT NULL DEFAULT '3000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `fortress` (
  `fortressID` int NOT NULL,
  `owner` int NOT NULL,
  `stone` bigint NOT NULL DEFAULT '0',
  `wood` bigint NOT NULL DEFAULT '0',
  `u1` tinyint NOT NULL DEFAULT '0',
  `u2` tinyint NOT NULL DEFAULT '0',
  `u3` tinyint NOT NULL DEFAULT '0',
  `ul1` tinyint NOT NULL DEFAULT '1',
  `ul2` tinyint DEFAULT '1',
  `ul3` tinyint NOT NULL DEFAULT '1',
  `ut1` tinyint NOT NULL DEFAULT '0',
  `ut2` tinyint NOT NULL DEFAULT '0',
  `ut3` tinyint NOT NULL DEFAULT '0',
  `uttime1` int NOT NULL DEFAULT '0',
  `uttime2` int NOT NULL DEFAULT '0',
  `uttime3` int NOT NULL DEFAULT '0',
  `enemyid` int NOT NULL DEFAULT '0',
  `enemytime` int NOT NULL DEFAULT '0',
  `build_id` tinyint NOT NULL DEFAULT '0',
  `build_start` int NOT NULL DEFAULT '0',
  `build_end` int NOT NULL DEFAULT '0',
  `dig_start` int NOT NULL DEFAULT '0',
  `dig_end` int NOT NULL DEFAULT '0',
  `gather1` int NOT NULL DEFAULT '0',
  `gather2` int NOT NULL DEFAULT '0',
  `gather3` int NOT NULL DEFAULT '0',
  `hok` tinyint NOT NULL DEFAULT '0',
  `b0` tinyint NOT NULL DEFAULT '0',
  `b1` tinyint NOT NULL DEFAULT '0',
  `b2` tinyint NOT NULL DEFAULT '0',
  `b3` tinyint NOT NULL DEFAULT '0',
  `b4` tinyint NOT NULL DEFAULT '0',
  `b5` tinyint NOT NULL DEFAULT '0',
  `b6` tinyint NOT NULL DEFAULT '0',
  `b7` tinyint NOT NULL DEFAULT '0',
  `b8` tinyint NOT NULL DEFAULT '0',
  `b9` tinyint NOT NULL DEFAULT '0',
  `b10` tinyint NOT NULL DEFAULT '0',
  `b11` tinyint NOT NULL DEFAULT '0',
  `forthonor` int NOT NULL DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `guildchat` (
  `ID` int NOT NULL,
  `guildID` int NOT NULL,
  `playerID` int NOT NULL,
  `message` char(255) NOT NULL,
  `time` int NOT NULL,
  `chattime` int NOT NULL,
  `spec` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `guildfightlogs` (
  `ID` int NOT NULL,
  `guildAttacker` int NOT NULL,
  `guildDefender` int NOT NULL,
  `log` mediumtext NOT NULL,
  `endLog` text NOT NULL,
  `time` int NOT NULL,
  `save` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `guildfights` (
  `ID` int NOT NULL,
  `guildAttacker` int NOT NULL,
  `guildDefender` int NOT NULL,
  `time` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `guildinvites` (
  `ID` int NOT NULL,
  `guildID` int NOT NULL,
  `playerID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `guilds` (
  `ID` int NOT NULL,
  `name` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `base` tinyint NOT NULL DEFAULT '10',
  `treasure` int NOT NULL DEFAULT '0',
  `instructor` int NOT NULL DEFAULT '0',
  `silver` int NOT NULL DEFAULT '1000',
  `mush` int NOT NULL DEFAULT '0',
  `honor` int NOT NULL DEFAULT '100',
  `portal` tinyint NOT NULL DEFAULT '0',
  `portal_hp` bigint NOT NULL DEFAULT '1003472384',
  `dungeon` tinyint NOT NULL DEFAULT '0',
  `attack_init` int NOT NULL DEFAULT '0',
  `event_trigger_count` int NOT NULL DEFAULT '0',
  `descr` text NOT NULL,
  `catapult` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `guildslots` (
  `ID` int NOT NULL,
  `guildID` int NOT NULL,
  `country` int NOT NULL,
  `minLvl` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `ipbans` (
  `id` int NOT NULL,
  `ip` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

CREATE TABLE `items` (
  `ID` int NOT NULL,
  `owner` int NOT NULL,
  `slot` int NOT NULL,
  `type` int NOT NULL,
  `item_id` int NOT NULL,
  `dmg_min` int NOT NULL DEFAULT '0',
  `dmg_max` int NOT NULL DEFAULT '0',
  `a1` int NOT NULL DEFAULT '0',
  `a2` int NOT NULL DEFAULT '0',
  `a3` int NOT NULL DEFAULT '0',
  `a4` int NOT NULL DEFAULT '0',
  `a5` int NOT NULL DEFAULT '0',
  `a6` int DEFAULT '0',
  `value_silver` bigint NOT NULL,
  `value_mush` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `messages` (
  `ID` int NOT NULL,
  `sender` int NOT NULL,
  `reciver` int NOT NULL,
  `time` int NOT NULL,
  `topic` char(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `hasRead` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `playerfightlogs` (
  `ID` int NOT NULL,
  `attacker` int NOT NULL,
  `defender` int NOT NULL,
  `log` mediumtext NOT NULL,
  `endlog` text NOT NULL,
  `time` int NOT NULL,
  `save` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `players` (
  `ID` int NOT NULL,
  `name` char(26) NOT NULL,
  `password` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `ip` text NOT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `email` text NOT NULL,
  `gframe` tinyint(1) NOT NULL DEFAULT '0',
  `perm` tinyint NOT NULL DEFAULT '0',
  `face` char(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `description` text NOT NULL,
  `newday` bigint NOT NULL DEFAULT '0',
  `ssid` char(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `wheel` tinyint(1) DEFAULT '0',
  `wheelcounts` int NOT NULL DEFAULT '0',
  `newwheel` bigint NOT NULL DEFAULT '0',
  `poll` int NOT NULL DEFAULT '0',
  `lvl` smallint NOT NULL DEFAULT '1',
  `exp` text NOT NULL,
  `expType` tinyint(1) NOT NULL DEFAULT '0',
  `quest_dur1` smallint NOT NULL,
  `quest_dur2` smallint NOT NULL,
  `quest_dur3` smallint NOT NULL,
  `quest_exp1` bigint UNSIGNED NOT NULL,
  `quest_exp2` bigint UNSIGNED NOT NULL,
  `quest_exp3` bigint UNSIGNED NOT NULL,
  `quest_silver1` bigint UNSIGNED NOT NULL,
  `quest_silver2` bigint UNSIGNED NOT NULL,
  `quest_silver3` bigint UNSIGNED NOT NULL,
  `quest_start` int NOT NULL,
  `str` bigint NOT NULL DEFAULT '10',
  `dex` bigint NOT NULL DEFAULT '10',
  `intel` bigint NOT NULL DEFAULT '10',
  `wit` bigint NOT NULL DEFAULT '10',
  `luck` bigint NOT NULL DEFAULT '10',
  `potion_type1` tinyint NOT NULL DEFAULT '0',
  `potion_type2` tinyint NOT NULL DEFAULT '0',
  `potion_type3` tinyint NOT NULL DEFAULT '0',
  `potion_dur1` int NOT NULL DEFAULT '0',
  `potion_dur2` int NOT NULL DEFAULT '0',
  `potion_dur3` int NOT NULL DEFAULT '0',
  `silver` bigint NOT NULL DEFAULT '0',
  `mush` int NOT NULL DEFAULT '0',
  `thirst` smallint NOT NULL DEFAULT '6000',
  `beers` tinyint NOT NULL DEFAULT '0',
  `honor` bigint NOT NULL DEFAULT '100',
  `pethonor` int NOT NULL DEFAULT '100',
  `forthonor` int NOT NULL DEFAULT '100',
  `race` tinyint(1) NOT NULL,
  `gender` tinyint(1) NOT NULL,
  `class` tinyint(1) NOT NULL,
  `mount` tinyint NOT NULL DEFAULT '0',
  `mount_time` int NOT NULL DEFAULT '0',
  `hourglass` bigint NOT NULL DEFAULT '0',
  `album` smallint NOT NULL DEFAULT '-1',
  `album_data` varchar(1024) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `status_extra` tinyint NOT NULL DEFAULT '0',
  `status_time` int NOT NULL DEFAULT '0',
  `dungeon_time` int NOT NULL DEFAULT '0',
  `arena_time` int NOT NULL DEFAULT '0',
  `arena_nme1` int NOT NULL DEFAULT '0',
  `arena_nme2` int NOT NULL DEFAULT '0',
  `arena_nme3` int NOT NULL DEFAULT '0',
  `tower` tinyint NOT NULL DEFAULT '0',
  `wcaura` smallint NOT NULL DEFAULT '1',
  `wcexp` smallint NOT NULL DEFAULT '0',
  `wcdate` int NOT NULL DEFAULT '0',
  `portal` tinyint NOT NULL DEFAULT '0',
  `portal_hp` bigint NOT NULL DEFAULT '35938800',
  `portal_time` smallint NOT NULL DEFAULT '0',
  `gportal_time` int NOT NULL DEFAULT '0',
  `guild` int NOT NULL DEFAULT '0',
  `guild_rank` tinyint NOT NULL DEFAULT '3',
  `guild_fight` tinyint NOT NULL DEFAULT '0',
  `event_trigger_count` int NOT NULL DEFAULT '0',
  `pets` text NOT NULL,
  `petsDung` text NOT NULL,
  `petsFed` text NOT NULL,
  `petsPvP` text NOT NULL,
  `petsBest1` int NOT NULL DEFAULT '0',
  `petsBest2` int NOT NULL DEFAULT '0',
  `petsBest3` int NOT NULL DEFAULT '0',
  `mirror` int NOT NULL DEFAULT '0',
  `donatesilver` bigint NOT NULL DEFAULT '0',
  `donatemush` bigint NOT NULL DEFAULT '0',
  `whisper` text NOT NULL,
  `fightswon` bigint NOT NULL DEFAULT '0',
  `questsdone` bigint NOT NULL DEFAULT '0',
  `workedhours` bigint NOT NULL DEFAULT '0',
  `maxhonor` bigint NOT NULL DEFAULT '0',
  `invites` text NOT NULL,
  `friends` text NOT NULL,
  `blacksmith` text NOT NULL,
  `achiData` text NOT NULL,
  `noinv` tinyint(1) NOT NULL DEFAULT '0',
  `flag` text NOT NULL,
  `language` text,
  `tutorial` int NOT NULL DEFAULT '0',
  `luckycoin` int NOT NULL DEFAULT '0',
  `accountsave` tinyint(1) NOT NULL DEFAULT '0',
  `renew` int NOT NULL DEFAULT '0',
  `lightdungeons` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `shadowdungeons` text,
  `idols` int NOT NULL DEFAULT '0',
  `twister` int NOT NULL DEFAULT '0',
  `unlockmirror` int NOT NULL DEFAULT '0',
  `petnest` int NOT NULL DEFAULT '0',
  `petsunlock` text,
  `food_black` int NOT NULL,
  `food_orange` int NOT NULL,
  `food_green` int NOT NULL,
  `food_red` int NOT NULL,
  `food_blue` int NOT NULL,
  `skill_treasure` int NOT NULL DEFAULT '0',
  `skill_instructor` int NOT NULL DEFAULT '0',
  `skill_pet` int NOT NULL DEFAULT '0',
  `calenderDay` int DEFAULT NULL,
  `calenderNext` int DEFAULT NULL,
  `dungeonLightResetCount` int NOT NULL DEFAULT '0',
  `dungeonShadowResetCount` int NOT NULL DEFAULT '0',
  `towerResetCount` int NOT NULL DEFAULT '0',
  `twisterResetCount` int NOT NULL DEFAULT '0',
  `idolsResetCount` int NOT NULL DEFAULT '0',
  `portrait` int NOT NULL DEFAULT '0',
  `usysclass` int NOT NULL DEFAULT '1',
  `voucherToday` int NOT NULL DEFAULT '0',
  `acpSession` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `underworld` (
  `ID` int NOT NULL,
  `owner` int NOT NULL,
  `soul` bigint NOT NULL DEFAULT '0',
  `heart` tinyint NOT NULL DEFAULT '1',
  `gate` tinyint NOT NULL DEFAULT '0',
  `torture` tinyint NOT NULL DEFAULT '0',
  `keeper` tinyint NOT NULL DEFAULT '0',
  `extractor` tinyint NOT NULL DEFAULT '0',
  `goblin` tinyint NOT NULL DEFAULT '0',
  `gladiator` tinyint NOT NULL DEFAULT '0',
  `troll` tinyint NOT NULL DEFAULT '0',
  `gold` tinyint NOT NULL DEFAULT '0',
  `time` tinyint NOT NULL DEFAULT '0',
  `build_id` tinyint NOT NULL DEFAULT '0',
  `build_start` int NOT NULL DEFAULT '0',
  `build_end` int NOT NULL DEFAULT '0',
  `gather1` int NOT NULL DEFAULT '0',
  `gather2` int NOT NULL DEFAULT '0',
  `gather3` int NOT NULL DEFAULT '0',
  `claim_gold` bigint NOT NULL DEFAULT '0',
  `lured` smallint NOT NULL DEFAULT '0',
  `unit_goblins` int NOT NULL DEFAULT '0',
  `unit_trolls` int NOT NULL DEFAULT '0',
  `unit_keeper` int NOT NULL DEFAULT '0',
  `battle_lvl` smallint NOT NULL DEFAULT '0',
  `timeamount` smallint NOT NULL DEFAULT '0',
  `uwhonor` int NOT NULL DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `vouchers` (
  `ID` int NOT NULL,
  `type` text NOT NULL,
  `code` text NOT NULL,
  `uses` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `witch` (
  `id` int NOT NULL,
  `fill` bigint NOT NULL DEFAULT '0',
  `max` bigint NOT NULL DEFAULT '100000',
  `event` tinyint NOT NULL DEFAULT '0',
  `eventtime` bigint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;


INSERT INTO `witch` (`id`, `fill`, `max`, `event`, `eventtime`) VALUES
(1, 1, 100000, 1, 115);

ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `copycats`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `owner` (`owner`);

ALTER TABLE `fortress`
  ADD PRIMARY KEY (`fortressID`),
  ADD KEY `owner` (`owner`),
  ADD KEY `owner_2` (`owner`);

ALTER TABLE `guildchat`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `guildID` (`guildID`),
  ADD KEY `chattime` (`chattime`);

ALTER TABLE `guildfightlogs`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `guildAttacker` (`guildAttacker`),
  ADD KEY `guildDefender` (`guildDefender`),
  ADD KEY `time` (`time`);

ALTER TABLE `guildfights`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `guildAttacker` (`guildAttacker`),
  ADD KEY `guildDefender` (`guildDefender`);

ALTER TABLE `guildinvites`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `guildID` (`guildID`);

ALTER TABLE `guilds`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `honor` (`honor`),
  ADD KEY `name` (`name`);

ALTER TABLE `guildslots`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `ipbans`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `items`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `owner` (`owner`);

ALTER TABLE `messages`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `reciver` (`reciver`);

ALTER TABLE `playerfightlogs`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `time` (`time`),
  ADD KEY `attacker` (`attacker`),
  ADD KEY `defender` (`defender`);

ALTER TABLE `players`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ssid` (`ssid`),
  ADD KEY `guild` (`guild`),
  ADD KEY `honor` (`honor`),
  ADD KEY `name` (`name`),
  ADD KEY `pethonor` (`pethonor`),
  ADD KEY `forthonor` (`forthonor`);

ALTER TABLE `underworld`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `witch`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `chat_messages`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `copycats`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `fortress`
  MODIFY `fortressID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `guildchat`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `guildfightlogs`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `guildfights`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `guildinvites`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `guilds`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `guildslots`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `ipbans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `items`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `messages`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `playerfightlogs`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `players`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `underworld`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `vouchers`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `witch`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
