import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:timezone/data/latest_all.dart' as tz_data;
import 'package:timezone/timezone.dart' as tz;

import '../database/app_database.dart';

const _enabledKey = 'daily_reminders_enabled';
const _maghribMinutesKey = 'maghrib_reminder_minutes';
const _ishaMinutesKey = 'isha_reminder_minutes';

class ReminderPreferences {
  const ReminderPreferences({
    required this.enabled,
    required this.maghribMinutes,
    required this.ishaMinutes,
  });

  final bool enabled;
  final int maghribMinutes;
  final int ishaMinutes;

  TimeOfDay get maghribTime =>
      TimeOfDay(hour: maghribMinutes ~/ 60, minute: maghribMinutes % 60);

  TimeOfDay get ishaTime =>
      TimeOfDay(hour: ishaMinutes ~/ 60, minute: ishaMinutes % 60);
}

class DailyReminderService {
  DailyReminderService(this.database);

  final AppDatabase database;

  static final FlutterLocalNotificationsPlugin _notifications =
      FlutterLocalNotificationsPlugin();
  static bool _initialized = false;
  static const _maghribId = 4101;
  static const _ishaId = 4102;

  Future<void> initialize() async {
    if (_initialized) return;
    tz_data.initializeTimeZones();
    tz.setLocalLocation(tz.getLocation('Asia/Hebron'));
    const settings = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(
        requestAlertPermission: false,
        requestBadgePermission: false,
        requestSoundPermission: false,
      ),
    );
    await _notifications.initialize(settings: settings);
    _initialized = true;
  }

  Future<ReminderPreferences> preferences() async {
    final enabled = await database.setting(_enabledKey);
    final maghrib = int.tryParse(
      await database.setting(_maghribMinutesKey) ?? '',
    );
    final isha = int.tryParse(await database.setting(_ishaMinutesKey) ?? '');
    return ReminderPreferences(
      enabled: enabled != '0',
      maghribMinutes: maghrib ?? 18 * 60,
      ishaMinutes: isha ?? 20 * 60 + 30,
    );
  }

  Future<void> savePreferences(ReminderPreferences value) async {
    await database.setSetting(_enabledKey, value.enabled ? '1' : '0');
    await database.setSetting(
      _maghribMinutesKey,
      value.maghribMinutes.toString(),
    );
    await database.setSetting(_ishaMinutesKey, value.ishaMinutes.toString());
  }

  Future<bool> requestPermission() async {
    await initialize();
    return await _notifications
            .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin
            >()
            ?.requestNotificationsPermission() ??
        true;
  }

  Future<void> cancelScheduled() async {
    await initialize();
    await _notifications.cancel(id: _maghribId);
    await _notifications.cancel(id: _ishaId);
  }

  Future<void> refreshSchedule() async {
    await initialize();
    final values = await preferences();
    await _notifications.cancel(id: _maghribId);
    await _notifications.cancel(id: _ishaId);
    if (!values.enabled) return;

    final complete = await database.isDailyWorkComplete(DateTime.now());
    final earliestDay = complete
        ? tz.TZDateTime.now(tz.local).add(const Duration(days: 1))
        : tz.TZDateTime.now(tz.local);

    await _scheduleDaily(
      id: _maghribId,
      minutes: values.maghribMinutes,
      earliestDay: earliestDay,
      title: 'تذكير السجل اليومي',
      body: 'حان وقت مراجعة حضور الطلاب وتسجيل الحفظ اليومي.',
    );
    await _scheduleDaily(
      id: _ishaId,
      minutes: values.ishaMinutes,
      earliestDay: earliestDay,
      title: 'هل اكتمل سجل الحلقة؟',
      body: 'تأكد بعد العشاء من تعبئة سجلات الطلاب قبل انتهاء اليوم.',
    );
  }

  Future<void> _scheduleDaily({
    required int id,
    required int minutes,
    required tz.TZDateTime earliestDay,
    required String title,
    required String body,
  }) async {
    var scheduled = tz.TZDateTime(
      tz.local,
      earliestDay.year,
      earliestDay.month,
      earliestDay.day,
      minutes ~/ 60,
      minutes % 60,
    );
    final now = tz.TZDateTime.now(tz.local);
    if (!scheduled.isAfter(now)) {
      scheduled = scheduled.add(const Duration(days: 1));
    }

    await _notifications.zonedSchedule(
      id: id,
      title: title,
      body: body,
      scheduledDate: scheduled,
      notificationDetails: const NotificationDetails(
        android: AndroidNotificationDetails(
          'daily_record_reminders',
          'تذكيرات السجل اليومي',
          channelDescription: 'تذكير المحفّظ بتسجيل حضور الطلاب وحفظهم',
          importance: Importance.high,
          priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
      ),
      androidScheduleMode: AndroidScheduleMode.inexactAllowWhileIdle,
      matchDateTimeComponents: DateTimeComponents.time,
    );
  }
}
