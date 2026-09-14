import 'dart:convert';

Map<String, dynamic> _map(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

List<Map<String, dynamic>> _maps(dynamic value) => value is List
    ? value.whereType<Map>().map(Map<String, dynamic>.from).toList()
    : const <Map<String, dynamic>>[];

String? _string(dynamic value) {
  if (value == null) return null;
  final text = value.toString().trim();
  return text.isEmpty ? null : text;
}

int? _integer(dynamic value) => value is int
    ? value
    : value is num
    ? value.toInt()
    : int.tryParse(value?.toString() ?? '');

double? _decimal(dynamic value) =>
    value is num ? value.toDouble() : double.tryParse(value?.toString() ?? '');

class TeacherProfileSnapshot {
  const TeacherProfileSnapshot({
    required this.id,
    required this.name,
    this.email,
    this.phone,
    this.employeeNumber,
    this.identityNumber,
    this.specialization,
    this.hiredAt,
    this.centerName,
    this.avatarUrl,
    this.roles = const [],
    this.permissions = const [],
    this.canExportReports = false,
    this.canCreateStudents = false,
    this.canUpdateStudents = false,
    this.canArchiveStudents = false,
  });

  final int id;
  final String name;
  final String? email;
  final String? phone;
  final String? employeeNumber;
  final String? identityNumber;
  final String? specialization;
  final String? hiredAt;
  final String? centerName;
  final String? avatarUrl;
  final List<String> roles;
  final List<String> permissions;
  final bool canExportReports;
  final bool canCreateStudents;
  final bool canUpdateStudents;
  final bool canArchiveStudents;

  factory TeacherProfileSnapshot.fromJson(Map<String, dynamic> json) {
    final profile = _map(json['profile']);
    final center = _map(profile['center'] ?? json['center']);
    final avatar = _map(json['avatar'] ?? profile['avatar']);
    final capabilities = _map(json['capabilities']);
    return TeacherProfileSnapshot(
      id: _integer(profile['id'] ?? json['id']) ?? 0,
      name: _string(json['name'] ?? profile['name']) ?? 'المحفّظ',
      email: _string(json['email'] ?? profile['email']),
      phone: _string(json['phone'] ?? profile['phone']),
      employeeNumber: _string(
        profile['employee_number'] ?? json['employee_number'],
      ),
      identityNumber: _string(
        profile['identity_number'] ?? json['identity_number'],
      ),
      specialization: _string(
        profile['specialization'] ?? json['specialization'],
      ),
      hiredAt: _string(profile['hired_at'] ?? json['hired_at']),
      centerName: _string(center['name'] ?? json['center_name']),
      avatarUrl: _string(avatar['url'] ?? json['avatar_url']),
      roles: (json['roles'] as List? ?? const [])
          .map((item) => item.toString())
          .toList(growable: false),
      permissions: (json['permissions'] as List? ?? const [])
          .map((item) => item.toString())
          .toList(growable: false),
      canExportReports: json['can_export_reports'] == true,
      canCreateStudents:
          json['can_create_students'] == true ||
          capabilities['create_students'] == true,
      canUpdateStudents:
          json['can_update_students'] == true ||
          capabilities['update_students'] == true,
      canArchiveStudents:
          json['can_archive_students'] == true ||
          capabilities['archive_students'] == true,
    );
  }

  factory TeacherProfileSnapshot.fromEncoded(String payload) =>
      TeacherProfileSnapshot.fromJson(
        Map<String, dynamic>.from(jsonDecode(payload) as Map),
      );
}

class StudentProfileSnapshot {
  const StudentProfileSnapshot({
    required this.id,
    required this.studentNumber,
    required this.fullName,
    this.birthDate,
    this.registrationDate,
    this.status,
    this.statusLabel,
    this.contactPhone,
    this.firstName,
    this.fatherName,
    this.grandfatherName,
    this.familyName,
    this.notes,
    this.updatedAt,
    this.photoUrl,
    this.progress,
    this.journey,
    this.achievements = const [],
    this.recentRecords = const [],
  });

  final int id;
  final String studentNumber;
  final String fullName;
  final String? birthDate;
  final String? registrationDate;
  final String? status;
  final String? statusLabel;
  final String? contactPhone;
  final String? firstName;
  final String? fatherName;
  final String? grandfatherName;
  final String? familyName;
  final String? notes;
  final String? updatedAt;
  final String? photoUrl;
  final StudentProgressSnapshotData? progress;
  final MemorizationJourneySnapshot? journey;
  final List<AchievementSnapshot> achievements;
  final List<RecentDailyRecordSnapshot> recentRecords;

  factory StudentProfileSnapshot.fromJson(Map<String, dynamic> json) {
    final profile = _map(json['profile']);
    final progressJson = _map(
      profile['progress'] ?? json['progress'] ?? json['latest_progress'],
    );
    final journeyJson = _map(
      progressJson['memorization_journey'] ??
          profile['journey'] ??
          json['journey'] ??
          json['memorization_journey'],
    );
    final photo = _map(profile['photo'] ?? json['photo']);
    return StudentProfileSnapshot(
      id: _integer(json['id'] ?? profile['id']) ?? 0,
      studentNumber:
          _string(json['student_number'] ?? profile['student_number']) ?? '',
      fullName: _string(json['full_name'] ?? profile['full_name']) ?? '',
      birthDate: _string(profile['birth_date'] ?? json['birth_date']),
      registrationDate: _string(
        profile['registration_date'] ?? json['registration_date'],
      ),
      status: _string(profile['status'] ?? json['status']),
      statusLabel: _string(profile['status_label'] ?? json['status_label']),
      contactPhone: _string(profile['contact_phone']),
      firstName: _string(profile['first_name'] ?? json['first_name']),
      fatherName: _string(profile['father_name'] ?? json['father_name']),
      grandfatherName: _string(
        profile['grandfather_name'] ?? json['grandfather_name'],
      ),
      familyName: _string(profile['family_name'] ?? json['family_name']),
      notes: _string(profile['notes'] ?? json['notes']),
      updatedAt: _string(profile['updated_at'] ?? json['updated_at']),
      photoUrl: _string(
        photo['url'] ?? profile['photo_url'] ?? json['photo_url'],
      ),
      progress: progressJson.isEmpty
          ? null
          : StudentProgressSnapshotData.fromJson(progressJson),
      journey: journeyJson.isEmpty
          ? null
          : MemorizationJourneySnapshot.fromJson(journeyJson),
      achievements: _maps(
        profile['achievements'] ?? json['achievements'],
      ).map(AchievementSnapshot.fromJson).toList(growable: false),
      recentRecords: _maps(
        profile['recent_records'] ?? json['recent_records'],
      ).map(RecentDailyRecordSnapshot.fromJson).toList(growable: false),
    );
  }

  factory StudentProfileSnapshot.fromEncoded(String payload) =>
      StudentProfileSnapshot.fromJson(
        Map<String, dynamic>.from(jsonDecode(payload) as Map),
      );
}

class StudentProgressSnapshotData {
  const StudentProgressSnapshotData({
    this.asOfDate,
    this.memorizedAyahs = 0,
    this.memorizedPercentage = 0,
    this.completedSurahs = 0,
    this.completedJuz = 0,
    this.memorizationSessions = 0,
    this.revisionSessions = 0,
    this.lastRevisionAt,
    this.evaluationAverage,
    this.attendanceRate,
    this.score,
    this.performanceTrend,
  });

  final String? asOfDate;
  final int memorizedAyahs;
  final double memorizedPercentage;
  final int completedSurahs;
  final int completedJuz;
  final int memorizationSessions;
  final int revisionSessions;
  final String? lastRevisionAt;
  final double? evaluationAverage;
  final double? attendanceRate;
  final double? score;
  final double? performanceTrend;

  factory StudentProgressSnapshotData.fromJson(Map<String, dynamic> json) =>
      StudentProgressSnapshotData(
        asOfDate: _string(json['as_of_date']),
        memorizedAyahs: _integer(json['memorized_ayahs']) ?? 0,
        memorizedPercentage: _decimal(json['memorized_percentage']) ?? 0,
        completedSurahs: _integer(json['completed_surahs']) ?? 0,
        completedJuz: _integer(json['completed_juz']) ?? 0,
        memorizationSessions: _integer(json['memorization_sessions']) ?? 0,
        revisionSessions: _integer(json['revision_sessions']) ?? 0,
        lastRevisionAt: _string(json['last_revision_at']),
        evaluationAverage: _decimal(json['evaluation_average']),
        attendanceRate: _decimal(json['attendance_rate']),
        score: _decimal(json['score']),
        performanceTrend: _decimal(json['performance_trend']),
      );
}

class MemorizationJourneySnapshot {
  const MemorizationJourneySnapshot({
    this.hasProgress = false,
    this.completedJuz = 0,
    this.remainingJuz = 30,
    this.completedPercentage = 0,
    this.currentSurahName,
    this.currentAyahNumber,
    this.encouragementTitle,
    this.encouragementMessage,
  });

  final bool hasProgress;
  final int completedJuz;
  final int remainingJuz;
  final double completedPercentage;
  final String? currentSurahName;
  final int? currentAyahNumber;
  final String? encouragementTitle;
  final String? encouragementMessage;

  factory MemorizationJourneySnapshot.fromJson(Map<String, dynamic> json) =>
      MemorizationJourneySnapshot(
        hasProgress: json['has_progress'] == true,
        completedJuz: _integer(json['completed_juz']) ?? 0,
        remainingJuz: _integer(json['remaining_juz']) ?? 30,
        completedPercentage: _decimal(json['completed_percentage']) ?? 0,
        currentSurahName: _string(json['frontier_surah_name']),
        currentAyahNumber: _integer(json['frontier_ayah_number']),
        encouragementTitle: _string(json['encouragement_title']),
        encouragementMessage: _string(json['encouragement_message']),
      );
}

class AchievementSnapshot {
  const AchievementSnapshot({
    required this.id,
    required this.title,
    this.typeLabel,
    this.description,
    this.achievedAt,
  });

  final int id;
  final String title;
  final String? typeLabel;
  final String? description;
  final String? achievedAt;

  factory AchievementSnapshot.fromJson(Map<String, dynamic> json) =>
      AchievementSnapshot(
        id: _integer(json['id']) ?? 0,
        title: _string(json['title']) ?? 'إنجاز',
        typeLabel: _string(json['type_label'] ?? json['type']),
        description: _string(json['description']),
        achievedAt: _string(json['achieved_at']),
      );
}

class RecentDailyRecordSnapshot {
  const RecentDailyRecordSnapshot({
    required this.id,
    this.recordDate,
    this.attendanceLabel,
    this.evaluationLabel,
    this.notes,
    this.recitations = const [],
  });

  final int id;
  final String? recordDate;
  final String? attendanceLabel;
  final String? evaluationLabel;
  final String? notes;
  final List<RecitationSummarySnapshot> recitations;

  factory RecentDailyRecordSnapshot.fromJson(Map<String, dynamic> json) {
    final attendance = _map(json['attendance']);
    return RecentDailyRecordSnapshot(
      id: _integer(json['id']) ?? 0,
      recordDate: _string(json['record_date']),
      attendanceLabel: _string(
        attendance['status_label'] ?? json['attendance_label'],
      ),
      evaluationLabel: _string(
        json['general_evaluation_label'] ?? json['general_evaluation'],
      ),
      notes: _string(json['notes']),
      recitations: _maps(
        json['recitations'] ?? json['items'],
      ).map(RecitationSummarySnapshot.fromJson).toList(growable: false),
    );
  }
}

class RecitationSummarySnapshot {
  const RecitationSummarySnapshot({
    this.typeLabel,
    this.startLabel,
    this.endLabel,
    this.evaluationLabel,
  });

  final String? typeLabel;
  final String? startLabel;
  final String? endLabel;
  final String? evaluationLabel;

  factory RecitationSummarySnapshot.fromJson(Map<String, dynamic> json) {
    final start = _map(json['start']);
    final end = _map(json['end']);
    return RecitationSummarySnapshot(
      typeLabel: _string(json['type_label'] ?? json['type']),
      startLabel:
          _string(json['start_label'] ?? start['label']) ?? _ayahLabel(start),
      endLabel: _string(json['end_label'] ?? end['label']) ?? _ayahLabel(end),
      evaluationLabel: _string(json['evaluation_label'] ?? json['evaluation']),
    );
  }

  static String? _ayahLabel(Map<String, dynamic> position) {
    final surah = _string(position['surah_name']);
    final ayah = _integer(position['ayah_number']);
    if (surah == null && ayah == null) return null;
    return [
      if (surah != null) 'سورة $surah',
      if (ayah != null) 'آية $ayah',
    ].join(' · ');
  }
}
